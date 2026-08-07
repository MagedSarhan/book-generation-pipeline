<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectPage;

class PromptComposer
{
    /**
     * Compose prompt payload for GPT Image 2 / Fal workflow request.
     */
    public function compose(
        Project $project,
        ProjectPage $page,
        ?string $userInstruction = null,
        ?string $batchInstruction = null,
        string $mode = 'redesign'
    ): string {
        $sections = [];

        // 1. Core Task Definition
        $sections[] = "TASK:\nProfessionally redesign one page of an Arabic RTL publication.";

        // 2. Image Role Definitions
        if ($mode === 'edit') {
            $sections[] = "IMAGE ROLE DEFINITIONS:\n" .
                "Image 1: Current generated page version to edit.\n" .
                "Image 2: Master visual style reference.\n" .
                "Image 3: Previous approved page (Page " . ($page->page_number - 1) . ") for visual continuity.\n" .
                "Image 4+: Additional reference materials or original source page.";
        } else {
            $sections[] = "IMAGE ROLE DEFINITIONS:\n" .
                "Image 1: Original imported page (content authority).\n" .
                "Image 2: Master visual identity & style reference.\n" .
                "Image 3: Previous approved page (Page " . ($page->page_number - 1) . ") for sequence continuity.\n" .
                "Image 4+: Additional style references.";
        }

        // 3. System Design Rules
        $sections[] = "EDITORIAL CONTENT & ACCURACY RULES:\n" .
            "- Maintain strict Arabic RTL layout and right-to-left reading flow.\n" .
            "- Preserve exact source text, headings, numbers, and facts accurately.\n" .
            "- Do not invent facts, sections, or copy text from style references.\n" .
            "- Ensure professional editorial hierarchy, elegant visual spacing, and clear typography.\n" .
            "- Do not add watermarks, mock UI artifacts, or unintended borders.";

        // 4. Master Project Style Prompt
        if (!empty($project->master_prompt)) {
            $sections[] = "PROJECT MASTER VISUAL PROMPT:\n" . trim($project->master_prompt);
        }

        // 5. Batch / Conversation Instruction
        if (!empty($batchInstruction)) {
            $sections[] = "BATCH DIRECTIVE:\n" . trim($batchInstruction);
        }

        // 6. Page-Specific User Instruction
        if (!empty($userInstruction)) {
            $sections[] = "PAGE SPECIFIC USER INSTRUCTION (PAGE {$page->page_number}):\n" . trim($userInstruction);
        }

        // 7. Extracted Text from Source Page if available
        if (!empty($page->extracted_text)) {
            $sections[] = "SOURCE PAGE EXTRACTED TEXT:\n" . trim($page->extracted_text);
        }

        return implode("\n\n", $sections);
    }
}

<?php

namespace App\Services;

use App\Contracts\GeneratesPrintableDocument;
use App\Enums\PrintableDocumentType;
use App\Models\PrintableDocument;
use Illuminate\Database\Eloquent\Model;

class PrintableDocumentService
{
    /** Returns saved HTML, generating it on first access. */
    public function getHtml(Model&GeneratesPrintableDocument $owner, PrintableDocumentType $type): string
    {
        $document = $this->firstOrCreate($owner, $type);

        if ($document->html === null) {
            $document->update(['html' => $this->render($owner, $type)]);
        }

        return $document->html;
    }

    /** Throws away manual edits and rebuilds the content from current data. */
    public function regenerate(Model&GeneratesPrintableDocument $owner, PrintableDocumentType $type): string
    {
        $html = $this->render($owner, $type);

        $this->firstOrCreate($owner, $type)->update(['html' => $html]);

        return $html;
    }

    /** Persists manually edited HTML coming from the browser's contenteditable. */
    public function save(Model $owner, PrintableDocumentType $type, string $html): void
    {
        $this->firstOrCreate($owner, $type)->update([
            'html' => $this->sanitize($html),
        ]);
    }

    protected function firstOrCreate(Model $owner, PrintableDocumentType $type): PrintableDocument
    {
        return PrintableDocument::firstOrCreate([
            'documentable_type' => $owner->getMorphClass(),
            'documentable_id'   => $owner->getKey(),
            'type'              => $type,
        ]);
    }

    protected function render(Model&GeneratesPrintableDocument $owner, PrintableDocumentType $type): string
    {
        return view(
            $owner->printableDocumentView($type),
            $owner->printableDocumentData($type)
        )->render();
    }

    // Keeps a safe tag allowlist - the HTML comes from contenteditable in the browser
    protected function sanitize(string $html): string
    {
        return strip_tags(
            $html,
            '<p><br><ul><ol><li><table><thead><tbody><tr><th><td><strong><em><b><i><u><div><span><hr><header><h1><h2>'
        );
    }
}
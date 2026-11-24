<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\PrintableDocumentService;
use Illuminate\Database\Eloquent\Model;

class PrintableDocumentViewer extends Component
{
    public string $documentableType;
    public int $documentableId;
    public string $type;
    public string $html = '';

    public function mount(Model $documentable, string $type): void
    {
        $this->documentableType = $documentable->getMorphClass();
        $this->documentableId   = $documentable->getKey();
        $this->type             = $type;
        $this->html = app(PrintableDocumentService::class)->getHtml($documentable, $type);
    }

    public function save(string $html): void
    {
        app(PrintableDocumentService::class)->save($this->documentable(), $this->type, $html);
        session()->flash('success', 'Zapisano zmiany.');
    }

    public function regenerate(): void
    {
        $this->html = app(PrintableDocumentService::class)->regenerate($this->documentable(), $this->type);
    }

    protected function documentable(): Model
    {
        return $this->documentableType::findOrFail($this->documentableId);
    }
}

<?php

namespace App\Traits;

/**
 * Dipakai oleh komponen Livewire admin untuk mengganti wire:confirm bawaan
 * browser dengan modal konfirmasi kustom + kata konfirmasi "HAPUS", supaya
 * panitia tidak menghapus data karena salah klik.
 */
trait ConfirmsDeletion
{
    public ?string $confirmDeleteId = null;

    public string $confirmDeleteMethod = 'delete';

    public string $confirmDeleteLabel = '';

    public string $confirmDeleteInput = '';

    /**
     * Buka modal konfirmasi untuk sebuah aksi hapus. $method adalah nama
     * method yang benar-benar menghapus data (dipanggil hanya setelah
     * kata konfirmasi benar).
     */
    public function confirmDelete(string $id, string $label = 'data ini', string $method = 'delete'): void
    {
        $this->confirmDeleteId = $id;
        $this->confirmDeleteLabel = $label;
        $this->confirmDeleteMethod = $method;
        $this->confirmDeleteInput = '';
        $this->resetErrorBag('confirmDeleteInput');
    }

    public function cancelDelete(): void
    {
        $this->reset(['confirmDeleteId', 'confirmDeleteMethod', 'confirmDeleteLabel', 'confirmDeleteInput']);
        $this->resetErrorBag('confirmDeleteInput');
    }

    public function executeDelete(): void
    {
        if (strcasecmp(trim($this->confirmDeleteInput), 'HAPUS') !== 0) {
            $this->addError('confirmDeleteInput', 'Ketik HAPUS untuk konfirmasi.');
            return;
        }

        $method = $this->confirmDeleteMethod;
        $id = $this->confirmDeleteId;
        $this->cancelDelete();

        if ($id !== null && method_exists($this, $method)) {
            $this->$method($id);
        }
    }
}

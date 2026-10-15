<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

/**
 * One key per opened create form; the document it creates stores the key in a
 * unique `submission_key` column, so one form can only ever create one document.
 * A form that creates several records in one save (the add-event popup) stamps
 * the key on all of them instead, with a plain index, and checks before saving.
 *
 * Why it's needed: Livewire re-enables a wire:submit button as soon as a
 * response arrives — including the one that redirects away — and the old page
 * stays clickable while the next one loads. A click in that gap (or one queued
 * while the first request ran) is a fresh request that would otherwise create a
 * second document.
 */
trait PreventsDuplicateSubmission
{
    #[Locked]
    public string $submissionKey = '';

    /**
     * Livewire trait hook, run on every request: a freshly mounted form gets its
     * key, later requests keep the one restored from the snapshot (properties are
     * hydrated before this runs). Not mount{Trait}: Livewire spreads the route
     * parameters into trait mount hooks, which throws on pages like /edit/{id}.
     */
    public function initializePreventsDuplicateSubmission(): void
    {
        if ($this->submissionKey === '') {
            $this->submissionKey = (string) Str::uuid();
        }
    }

    /**
     * Start a new form in a component that stays on the page between uses (a
     * popup opened, saved, opened again): what it saves next is a new document.
     */
    protected function renewSubmissionKey(): void
    {
        $this->submissionKey = (string) Str::uuid();
    }

    /**
     * The document this form already created, if any — deleted ones included,
     * so deleting it doesn't let a stale submit bring it back.
     *
     * @param class-string<Model> $modelClass
     */
    protected function alreadySubmitted(string $modelClass): ?Model
    {
        return $modelClass::withTrashed()
            ->where('submission_key', $this->submissionKey)
            ->first();
    }

    /** Two submits raced past alreadySubmitted() and the other one won. */
    protected function isDuplicateSubmission(\Throwable $e): bool
    {
        return $e instanceof UniqueConstraintViolationException
            && str_contains($e->getMessage(), 'submission_key');
    }
}

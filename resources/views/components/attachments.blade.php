@props(['document', 'type'])

@php
    $files = $document->attachments()->with('uploadedBy:id,name')->get();
    $isEditable = in_array($document->status, ['DRAFT', 'REJECTED'], true);
    $humanSize = fn (int $bytes) => $bytes >= 1048576 ? round($bytes / 1048576, 1).' MB' : max(1, round($bytes / 1024)).' KB';
@endphp

<div class="card mb-3" id="attachments">
    <div class="card-header">
        <h3 class="card-title"><i class="bi bi-paperclip"></i> {{ __('Attachments') }} <span class="badge text-bg-secondary">{{ $files->count() }}</span></h3>
    </div>
    <ul class="list-group list-group-flush">
        @forelse ($files as $file)
            <li class="list-group-item d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <div>
                    <a href="{{ route("finance.{$type}.attachments.download", [$document->id, $file->id]) }}"><i class="bi bi-file-earmark-arrow-down"></i> {{ $file->file_name }}</a>
                    <small class="text-body-secondary">· {{ $humanSize((int) $file->file_size) }} · {{ $file->uploadedBy?->name }} · {{ $file->created_at->format('Y-m-d H:i') }}</small>
                    @if ($file->description)
                        <div class="small">{{ $file->description }}</div>
                    @endif
                </div>
                @if ($isEditable && (int) $file->uploaded_by === (int) auth()->id())
                    <form method="POST" action="{{ route("finance.{$type}.attachments.destroy", [$document->id, $file->id]) }}" onsubmit="return confirm(@js(__('Remove this file?')))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-link text-danger">{{ __('Remove') }}</button>
                    </form>
                @endif
            </li>
        @empty
            <li class="list-group-item text-body-secondary">{{ __('No files attached.') }}</li>
        @endforelse
    </ul>
    @can("finance.{$type}.create")
        <div class="card-footer">
            <form method="POST" action="{{ route("finance.{$type}.attachments.store", $document->id) }}" enctype="multipart/form-data" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-5">
                    <label for="attachment-file" class="form-label">{{ __('Add a file') }}</label>
                    <input type="file" id="attachment-file" name="file" class="form-control @error('file') is-invalid @enderror" required>
                    @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label for="attachment-description" class="form-label">{{ __('Description') }}</label>
                    <input type="text" id="attachment-description" name="description" class="form-control" maxlength="255" placeholder="{{ __('e.g. supplier bill, signed contract') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-upload"></i> {{ __('Upload') }}</button>
                </div>
                <div class="col-12 form-text">{{ __('PDF, images, Office documents, CSV, XML or ZIP, up to 10 MB.') }}</div>
            </form>
        </div>
    @endcan
</div>

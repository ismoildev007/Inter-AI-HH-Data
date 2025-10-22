@php
    $durationValue = old('duration', optional($plan->duration)->format('Y-m-d'));
    $autoLimitValue = old('auto_response_limit', $plan->auto_response_limit ?? 0);
@endphp

<div class="row g-3">
    <div class="col-12">
        <label class="form-label text-uppercase fw-semibold small text-muted">Plan Name <span class="text-danger">*</span></label>
        <input type="text"
               name="name"
               class="form-control form-control-lg @error('name') is-invalid @enderror"
               value="{{ old('name', $plan->name) }}"
               placeholder="e.g. Starter, Scale Pro"
               required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label text-uppercase fw-semibold small text-muted">Description</label>
        <textarea name="description"
                  rows="4"
                  class="form-control @error('description') is-invalid @enderror"
                  placeholder="Short value proposition, limits or perks.">{{ old('description', $plan->description) }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Visible on the pricing section. Keep it concise and benefits-focused.</div>
    </div>

    <div class="col-md-4">
        <label class="form-label text-uppercase fw-semibold small text-muted">Fake Price</label>
        <div class="input-group">
            <span class="input-group-text">UZS</span>
            <input type="number"
                   step="0.01"
                   min="0"
                   name="fake_price"
                   class="form-control @error('fake_price') is-invalid @enderror"
                   value="{{ old('fake_price', $plan->fake_price) }}"
                   placeholder="999000.00">
            @error('fake_price')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-text">Optional crossed-out price for discounts.</div>
    </div>

    <div class="col-md-4">
        <label class="form-label text-uppercase fw-semibold small text-muted">Actual Price</label>
        <div class="input-group">
            <span class="input-group-text">UZS</span>
            <input type="number"
                   step="0.01"
                   min="0"
                   name="price"
                   class="form-control @error('price') is-invalid @enderror"
                   value="{{ old('price', $plan->price) }}"
                   placeholder="749000.00">
            @error('price')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-text">Current billing price used in checkout.</div>
    </div>

    <div class="col-md-4">
        <label class="form-label text-uppercase fw-semibold small text-muted">Auto Response Limit</label>
        <input type="number"
               min="0"
               step="1"
               name="auto_response_limit"
               class="form-control @error('auto_response_limit') is-invalid @enderror"
               value="{{ $autoLimitValue }}"
               placeholder="Number of automated responses">
        @error('auto_response_limit')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">How many AI auto-responses are bundled into this plan.</div>
    </div>

    <div class="col-md-6">
        <label class="form-label text-uppercase fw-semibold small text-muted">Duration</label>
        <input type="date"
               name="duration"
               class="form-control @error('duration') is-invalid @enderror"
               value="{{ $durationValue }}">
        @error('duration')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Optional expiry date for limited campaigns.</div>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between mt-4">
    <!-- <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">
        <i class="feather-arrow-left me-1"></i>
        Back to Plans
    </a> -->
    <button type="submit" class="btn btn-primary px-4">
        <i class="feather-save me-1"></i> {{ $submitLabel }}
    </button>
</div>

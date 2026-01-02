@include('admin.head')
@include('admin.sidebar')
@include('admin.navbar')

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">User Extra Codes</li>
                    </ol>
                </div>
                <h4 class="page-title">User Extra Codes Management</h4>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Assign Extra Codes Section -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-user-plus me-2"></i>Assign Extra Codes to Users
                        <span class="badge bg-primary ms-2">{{ $today->format('Y-m-d') }}</span>
                    </h5>

                    <form action="{{ route('user-extra-codes.store') }}" method="POST" id="extraCodesForm">
                        @csrf
                        
                        <!-- User Selection -->
                        <div class="mb-4">
                            <label class="form-label">
                                Select Users <span class="text-danger">*</span>
                                <small class="text-muted">(Hold Ctrl/Cmd to select multiple)</small>
                            </label>
                            <select 
                                name="user_ids[]" 
                                id="user_ids" 
                                class="form-select @error('user_ids') is-invalid @enderror" 
                                multiple 
                                size="8"
                                required
                                style="min-height: 200px;"
                            >
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name ?? $user->email }} ({{ $user->email }})
                                        @if($user->investments()->where('status', 'active')->exists())
                                            <span class="text-success">- Has Active Investment</span>
                                        @else
                                            <span class="text-warning">- No Active Investment</span>
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('user_ids')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Only users with active investments will receive mining sessions for the codes.
                            </small>
                        </div>

                        <!-- Codes Input -->
                        <div class="mb-4">
                            <label class="form-label">
                                Enter Codes <span class="text-danger">*</span>
                                <small class="text-muted">(One code per line or comma-separated)</small>
                            </label>
                            <textarea 
                                name="codes_input" 
                                id="codes_input" 
                                class="form-control @error('codes') is-invalid @enderror" 
                                rows="5" 
                                placeholder="Enter codes, one per line or comma-separated&#10;Example:&#10;CODE1&#10;CODE2&#10;CODE3"
                                required
                            >{{ old('codes_input') }}</textarea>
                            @error('codes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Each code will give the user half of the normal mining reward. Users can claim multiple codes.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Assign Codes to Selected Users
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Extra Codes List -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-list me-2"></i>Today's Extra Codes ({{ $today->format('Y-m-d') }})
                    </h5>

                    @if($todayExtraCodes->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No extra codes assigned for today.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Code</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($todayExtraCodes as $extraCode)
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong>{{ $extraCode->user->name ?? $extraCode->user->email }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $extraCode->user->email }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <code class="bg-light px-2 py-1 rounded">{{ $extraCode->code }}</code>
                                            </td>
                                            <td>
                                                @if($extraCode->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $extraCode->creator->name ?? $extraCode->creator->email ?? 'N/A' }}
                                            </td>
                                            <td>
                                                {{ $extraCode->created_at->format('Y-m-d H:i:s') }}
                                            </td>
                                            <td>
                                                <form action="{{ route('user-extra-codes.destroy', $extraCode->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this code?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('extraCodesForm').addEventListener('submit', function(e) {
    const codesInput = document.getElementById('codes_input').value.trim();
    if (!codesInput) {
        e.preventDefault();
        alert('Please enter at least one code.');
        return false;
    }
    
    // Parse codes (split by newline or comma)
    const codes = codesInput.split(/[\n,]+/).map(c => c.trim()).filter(c => c.length > 0);
    
    // Create hidden inputs for each code
    codes.forEach(code => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'codes[]';
        input.value = code;
        this.appendChild(input);
    });
});
</script>

@include('admin.footer')


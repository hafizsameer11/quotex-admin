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
                        <li class="breadcrumb-item active">Daily Mining Codes</li>
                    </ol>
                </div>
                <h4 class="page-title">Daily Mining Codes Management</h4>
            </div>
        </div>
    </div>

    <!-- Set Today's Codes -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-key me-2"></i>Set Today's Mining Codes
                            <span class="badge bg-primary ms-2">{{ $today->format('Y-m-d') }}</span>
                        </h5>
                        <a href="{{ route('daily-mining-codes.history') }}" class="btn btn-outline-primary">
                            <i class="fas fa-history me-2"></i>View Claim History
                        </a>
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

                    <form action="{{ route('daily-mining-codes.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="code1" class="form-label">
                                    Code 1 <span class="text-danger">*</span>
                                    @if($code1)
                                        <span class="badge bg-success ms-2">Active</span>
                                    @endif
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control @error('code1') is-invalid @enderror" 
                                    id="code1" 
                                    name="code1" 
                                    value="{{ old('code1', $code1->code ?? '') }}" 
                                    placeholder="Enter Code 1"
                                    maxlength="50"
                                    required
                                >
                                @error('code1')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="code2" class="form-label">
                                    Code 2 <span class="text-danger">*</span>
                                    @if($code2)
                                        <span class="badge bg-success ms-2">Active</span>
                                    @endif
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control @error('code2') is-invalid @enderror" 
                                    id="code2" 
                                    name="code2" 
                                    value="{{ old('code2', $code2->code ?? '') }}" 
                                    placeholder="Enter Code 2"
                                    maxlength="50"
                                    required
                                >
                                @error('code2')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> Setting new codes for today will deactivate any existing codes for today. 
                            Users must enter one of these codes to claim their mining rewards.
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Today's Codes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Codes History -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-history me-2"></i>Recent Codes (Last 7 Days)
                    </h5>

                    @if($recentCodes->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No codes have been set yet.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Code 1</th>
                                        <th>Code 2</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                        <th>Created At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $groupedCodes = $recentCodes->groupBy('date');
                                    @endphp
                                    @foreach($groupedCodes as $date => $codes)
                                        @php
                                            $code1 = $codes->where('code_type', 'code1')->first();
                                            $code2 = $codes->where('code_type', 'code2')->first();
                                            $isToday = \Carbon\Carbon::parse($date)->isToday();
                                        @endphp
                                        <tr class="{{ $isToday ? 'table-primary' : '' }}">
                                            <td>
                                                <strong>{{ \Carbon\Carbon::parse($date)->format('Y-m-d') }}</strong>
                                                @if($isToday)
                                                    <span class="badge bg-primary ms-2">Today</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($code1)
                                                    <code>{{ $code1->code }}</code>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($code2)
                                                    <code>{{ $code2->code }}</code>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($code1 && $code1->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($code1 && $code1->creator)
                                                    {{ $code1->creator->name ?? $code1->creator->email }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($code1)
                                                    {{ $code1->created_at->format('Y-m-d H:i') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
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

@include('admin.footer')


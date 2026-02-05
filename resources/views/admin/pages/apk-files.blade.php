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
                        <li class="breadcrumb-item active">APK Management</li>
                    </ol>
                </div>
                <h4 class="page-title">APK File Management</h4>
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

    <!-- Upload APK Section -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-upload me-2"></i>Upload New APK File
                    </h5>

                    <form action="{{ route('apk-files.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="apk_file" class="form-label">
                                    APK File <span class="text-danger">*</span>
                                    <small class="text-muted">(Max 100MB)</small>
                                </label>
                                <input 
                                    type="file" 
                                    class="form-control @error('apk_file') is-invalid @enderror" 
                                    id="apk_file" 
                                    name="apk_file" 
                                    accept=".apk"
                                    required
                                >
                                @error('apk_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="version" class="form-label">Version <small class="text-muted">(Optional)</small></label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="version" 
                                    name="version" 
                                    placeholder="e.g., 1.0.0"
                                    maxlength="50"
                                >
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <small class="text-muted">(Optional)</small></label>
                            <textarea 
                                class="form-control" 
                                id="description" 
                                name="description" 
                                rows="3"
                                placeholder="Enter description for this APK version..."
                                maxlength="500"
                            ></textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> Uploading a new APK will automatically set it as active and deactivate the previous one.
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload APK
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Active APK Info -->
    @if($activeApk)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-check-circle text-success me-2"></i>Currently Active APK
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>File Name:</strong> {{ $activeApk->original_name }}</p>
                            <p><strong>Version:</strong> {{ $activeApk->version ?? 'N/A' }}</p>
                            <p><strong>Size:</strong> {{ $activeApk->formatted_size }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Uploaded By:</strong> {{ $activeApk->uploader->name ?? $activeApk->uploader->email ?? 'N/A' }}</p>
                            <p><strong>Uploaded At:</strong> {{ $activeApk->created_at->format('Y-m-d H:i:s') }}</p>
                            <p><strong>Download URL:</strong> 
                                <a href="{{ route('api.apk.download') }}" target="_blank" class="text-primary">
                                    {{ route('api.apk.download') }}
                                </a>
                            </p>
                        </div>
                    </div>
                    @if($activeApk->description)
                        <p><strong>Description:</strong> {{ $activeApk->description }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- APK Files List -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-list me-2"></i>All APK Files
                    </h5>

                    @if($apkFiles->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No APK files uploaded yet.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Version</th>
                                        <th>Size</th>
                                        <th>Status</th>
                                        <th>Uploaded By</th>
                                        <th>Uploaded At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($apkFiles as $apk)
                                        <tr class="{{ $apk->is_active ? 'table-success' : '' }}">
                                            <td>
                                                <strong>{{ $apk->original_name }}</strong>
                                            </td>
                                            <td>{{ $apk->version ?? 'N/A' }}</td>
                                            <td>{{ $apk->formatted_size }}</td>
                                            <td>
                                                @if($apk->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>{{ $apk->uploader->name ?? $apk->uploader->email ?? 'N/A' }}</td>
                                            <td>{{ $apk->created_at->format('Y-m-d H:i:s') }}</td>
                                            <td>
                                                @if(!$apk->is_active)
                                                    <form action="{{ route('apk-files.set-active', $apk->id) }}" method="POST" style="display: inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fas fa-check"></i> Set Active
                                                        </button>
                                                    </form>
                                                @endif
                                                @if(!$apk->is_active)
                                                    <form action="{{ route('apk-files.destroy', $apk->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this APK?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-3">
                            {{ $apkFiles->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.footer')





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
                        <li class="breadcrumb-item"><a href="{{ route('daily-mining-codes.index') }}">Daily Mining Codes</a></li>
                        <li class="breadcrumb-item active">Claim History</li>
                    </ol>
                </div>
                <h4 class="page-title">Mining Code Claim History</h4>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-primary bg-soft">
                                <span class="avatar-title rounded-circle bg-primary text-white font-size-18">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-uppercase fw-medium text-muted mb-0">Total Claimed</p>
                            <h4 class="mb-0">{{ number_format($stats['total_claimed']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-success bg-soft">
                                <span class="avatar-title rounded-circle bg-success text-white font-size-18">
                                    <i class="fas fa-calendar-day"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-uppercase fw-medium text-muted mb-0">Today's Claims</p>
                            <h4 class="mb-0">{{ number_format($stats['today_claimed']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-info bg-soft">
                                <span class="avatar-title rounded-circle bg-info text-white font-size-18">
                                    <i class="fas fa-dollar-sign"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-uppercase fw-medium text-muted mb-0">Total Rewards</p>
                            <h4 class="mb-0">${{ number_format($stats['total_rewards'], 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Claim History Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-history me-2"></i>Claim History
                    </h5>

                    @if($claimedSessions->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No claims have been made yet.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Code</th>
                                        <th>Investment Plan</th>
                                        <th>Amount</th>
                                        <th>Reward</th>
                                        <th>Status</th>
                                        <th>Claimed At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($claimedSessions as $session)
                                        <tr>
                                            <td>
                                                <strong>{{ $session->code_date ? \Carbon\Carbon::parse($session->code_date)->format('Y-m-d') : 'N/A' }}</strong>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $session->user->name ?? $session->user->email }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $session->user->email }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <code class="bg-light px-2 py-1 rounded">{{ $session->used_code }}</code>
                                            </td>
                                            <td>
                                                @if($session->investment && $session->investment->investmentPlan)
                                                    <div>
                                                        <strong>{{ $session->investment->investmentPlan->plan_name ?? 'N/A' }}</strong>
                                                        <br>
                                                        <small class="text-muted">${{ number_format($session->investment->amount, 2) }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($session->investment)
                                                    ${{ number_format($session->investment->amount, 2) }}
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong class="text-success">${{ number_format($session->rewards_earned, 2) }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">Claimed</span>
                                            </td>
                                            <td>
                                                {{ $session->stopped_at ? \Carbon\Carbon::parse($session->stopped_at)->format('Y-m-d H:i:s') : 'N/A' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-3">
                            {{ $claimedSessions->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.footer')


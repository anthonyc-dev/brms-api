<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Backup Management - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 16px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-icon {
            font-size: 24px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .value {
            color: #333;
            font-size: 36px;
            font-weight: bold;
        }

        .stat-card .label {
            color: #999;
            font-size: 12px;
            margin-top: 5px;
        }

        .actions-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .actions-section h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .backup-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f85032 0%, #e73827 100%);
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
        }

        .backups-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .backups-section h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        tbody tr {
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-auto {
            background: #17a2b8;
            color: white;
        }

        .badge-manual {
            background: #6c757d;
            color: white;
        }

        .badge-complete {
            background: #28a745;
            color: white;
        }

        .badge-database {
            background: #007bff;
            color: white;
        }

        .badge-folders {
            background: #ffc107;
            color: #333;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        form {
            display: inline;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .backup-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🗄️ Backup Management System</h1>
            <p>Create, manage, and download database backups</p>
        </div>

        <!-- Alerts -->
        @if(session('success'))
        <div class="alert alert-success">
            <span class="alert-icon">✓</span>
            <div>
                <strong>Success!</strong> {{ session('success') }}
                @if(session('output'))
                <details style="margin-top: 10px;">
                    <summary style="cursor: pointer;">Show Details</summary>
                    <pre style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px; overflow-x: auto;">{{ session('output') }}</pre>
                </details>
                @endif
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-error">
            <span class="alert-icon">✕</span>
            <div><strong>Error!</strong> {{ session('error') }}</div>
        </div>
        @endif

        <!-- Scheduler Status -->
        <div class="actions-section" style="margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>⏰ Automated Backup Schedule</h2>
                @if($scheduler['is_active'])
                    <span class="badge badge-auto" style="font-size: 14px; padding: 8px 16px;">
                        ● Active
                    </span>
                @else
                    <span class="badge badge-manual" style="font-size: 14px; padding: 8px 16px;">
                        ○ Inactive
                    </span>
                @endif
            </div>

            <div class="stats-grid" style="margin-bottom: 0;">
                <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h3 style="color: rgba(255,255,255,0.8);">Schedule Time</h3>
                    <div class="value" style="color: white; font-size: 28px;">{{ $scheduler['schedule_time'] }}</div>
                    <div class="label" style="color: rgba(255,255,255,0.8);">{{ $scheduler['timezone'] }}</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
                    <h3 style="color: rgba(255,255,255,0.8);">Next Backup</h3>
                    <div class="value" style="color: white; font-size: 20px;">{{ $scheduler['next_run_human'] }}</div>
                    <div class="label" style="color: rgba(255,255,255,0.8);">{{ $scheduler['next_run'] }}</div>
                </div>
                <div class="stat-card">
                    <h3>Backup Type</h3>
                    <div class="value" style="font-size: 20px;">{{ $scheduler['backup_type'] }}</div>
                    <div class="label">Automated</div>
                </div>
                <div class="stat-card">
                    <h3>Retention</h3>
                    <div class="value">{{ $scheduler['retention_days'] }} Days</div>
                    <div class="label">Auto-cleanup enabled</div>
                </div>
                <div class="stat-card">
                    <h3>Auto Backups</h3>
                    <div class="value">{{ $scheduler['total_auto_backups'] }}</div>
                    <div class="label">Recent automated</div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Database</h3>
                <div class="value">{{ $statistics['database_name'] }}</div>
                <div class="label">Current Database</div>
            </div>
            <div class="stat-card">
                <h3>Tables</h3>
                <div class="value">{{ $statistics['total_tables'] }}</div>
                <div class="label">Total Tables</div>
            </div>
            <div class="stat-card">
                <h3>Records</h3>
                <div class="value">{{ number_format($statistics['total_records']) }}</div>
                <div class="label">Total Records</div>
            </div>
            <div class="stat-card">
                <h3>Files</h3>
                <div class="value">{{ $statistics['folder_zips'] }}</div>
                <div class="label">Folder Zips</div>
            </div>
            <div class="stat-card">
                <h3>Backups</h3>
                <div class="value">{{ count($backups) }}</div>
                <div class="label">Total Backups</div>
            </div>
        </div>

        <!-- Actions -->
        <div class="actions-section">
            <h2>Create New Backup</h2>
            <div class="backup-buttons">
                <form action="{{ route('backup.create') }}" method="POST" onsubmit="return confirm('Create complete system backup? This may take a few minutes.')">
                    @csrf
                    <input type="hidden" name="type" value="complete">
                    <button type="submit" class="btn btn-primary">
                        <span>🗄️</span>
                        Complete System Backup
                    </button>
                </form>

                <form action="{{ route('backup.create') }}" method="POST" onsubmit="return confirm('Create database backup?')">
                    @csrf
                    <input type="hidden" name="type" value="database">
                    <button type="submit" class="btn btn-success">
                        <span>💾</span>
                        Database Only
                    </button>
                </form>

                <form action="{{ route('backup.create') }}" method="POST" onsubmit="return confirm('Create folders backup?')">
                    @csrf
                    <input type="hidden" name="type" value="folders">
                    <button type="submit" class="btn btn-info">
                        <span>📁</span>
                        Folders Only
                    </button>
                </form>
            </div>
        </div>

        <!-- Backups List -->
        <div class="backups-section">
            <h2>Available Backups ({{ count($backups) }})</h2>

            @if(count($backups) > 0)
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Type</th>
                            <th>Source</th>
                            <th>Size</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backups as $backup)
                        <tr>
                            <td>
                                <strong>{{ $backup['filename'] }}</strong>
                            </td>
                            <td>
                                @if(strpos($backup['filename'], 'complete') !== false)
                                    <span class="badge badge-complete">{{ $backup['type'] }}</span>
                                @elseif(strpos($backup['filename'], 'database') !== false)
                                    <span class="badge badge-database">{{ $backup['type'] }}</span>
                                @else
                                    <span class="badge badge-folders">{{ $backup['type'] }}</span>
                                @endif
                            </td>
                            <td>
                                @if($backup['is_auto'])
                                    <span class="badge badge-auto">Automated</span>
                                @else
                                    <span class="badge badge-manual">Manual</span>
                                @endif
                            </td>
                            <td>{{ $backup['size_mb'] }} MB</td>
                            <td>{{ $backup['created_at'] }}</td>
                            <td>
                                <div class="action-buttons">
                                    <a href="{{ route('backup.download', $backup['filename']) }}" class="btn btn-success btn-sm">
                                        ⬇️ Download
                                    </a>
                                    <form action="{{ route('backup.delete', $backup['filename']) }}" method="POST" onsubmit="return confirm('Delete this backup? This action cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            🗑️ Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <h3>No Backups Found</h3>
                <p>Create your first backup using the buttons above</p>
            </div>
            @endif
        </div>
    </div>

    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Add loading state to form submissions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const button = this.querySelector('button[type="submit"]');
                if (button && !button.disabled) {
                    button.disabled = true;
                    const originalHTML = button.innerHTML;
                    button.innerHTML = '<span class="loading"></span> Processing...';

                    // Re-enable if form validation fails
                    setTimeout(() => {
                        if (!this.checkValidity()) {
                            button.disabled = false;
                            button.innerHTML = originalHTML;
                        }
                    }, 100);
                }
            });
        });
    </script>
</body>
</html>

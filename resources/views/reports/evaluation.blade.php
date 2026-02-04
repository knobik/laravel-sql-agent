<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Agent Evaluation Report</title>
    <style>
        :root {
            --color-pass: #22c55e;
            --color-fail: #ef4444;
            --color-error: #f59e0b;
            --color-bg: #f8fafc;
            --color-card: #ffffff;
            --color-border: #e2e8f0;
            --color-text: #1e293b;
            --color-text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            margin: 0;
            padding: 2rem;
            line-height: 1.5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            font-size: 1.875rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: var(--color-text-muted);
            margin-bottom: 2rem;
        }

        /* Summary Cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-radius: 0.5rem;
            padding: 1.25rem;
        }

        .card-label {
            font-size: 0.875rem;
            color: var(--color-text-muted);
            margin-bottom: 0.5rem;
        }

        .card-value {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .card-value.pass { color: var(--color-pass); }
        .card-value.fail { color: var(--color-fail); }
        .card-value.error { color: var(--color-error); }

        /* Category Stats */
        .category-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
        }

        .category-card {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-radius: 0.5rem;
            padding: 1rem;
        }

        .category-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-transform: capitalize;
        }

        .category-stats {
            font-size: 0.875rem;
            color: var(--color-text-muted);
        }

        .progress-bar {
            height: 0.5rem;
            background: var(--color-border);
            border-radius: 0.25rem;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--color-pass);
            border-radius: 0.25rem;
            transition: width 0.3s ease;
        }

        /* Results Table */
        .results-section {
            margin-bottom: 2rem;
        }

        .table-container {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--color-border);
        }

        th {
            background: var(--color-bg);
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--color-text-muted);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: var(--color-bg);
        }

        .status-icon {
            font-size: 1.25rem;
        }

        .status-pass { color: var(--color-pass); }
        .status-fail { color: var(--color-fail); }
        .status-error { color: var(--color-error); }

        .badge {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-category {
            background: #e0e7ff;
            color: #4338ca;
        }

        /* Failed Tests Detail */
        .failed-section {
            margin-top: 2rem;
        }

        .failed-test {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-left: 4px solid var(--color-fail);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .failed-test.error {
            border-left-color: var(--color-error);
        }

        .failed-test-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .failed-test-name {
            font-weight: 600;
        }

        .failed-test-content {
            font-size: 0.875rem;
        }

        .failed-test-content dt {
            font-weight: 500;
            color: var(--color-text-muted);
            margin-top: 0.5rem;
        }

        .failed-test-content dd {
            margin: 0.25rem 0 0;
        }

        .code-block {
            background: var(--color-bg);
            padding: 0.5rem;
            border-radius: 0.25rem;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 0.8125rem;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }

        /* Modes Info */
        .modes-info {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .mode-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }

        .mode-badge.active {
            background: #dcfce7;
            border-color: var(--color-pass);
        }

        .mode-badge .icon {
            font-size: 1rem;
        }

        /* Footer */
        .footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--color-border);
            font-size: 0.875rem;
            color: var(--color-text-muted);
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>SQL Agent Evaluation Report</h1>
        <p class="subtitle">
            Generated: {{ $report->completedAt->format('Y-m-d H:i:s') }}
            @if($report->category)
                | Category: {{ $report->category }}
            @endif
        </p>

        <!-- Modes Info -->
        <div class="modes-info">
            <div class="mode-badge active">
                <span class="icon">&#10003;</span>
                String Matching
            </div>
            <div class="mode-badge {{ $report->usedLlmGrader ? 'active' : '' }}">
                @if($report->usedLlmGrader)
                    <span class="icon">&#10003;</span>
                @else
                    <span class="icon">-</span>
                @endif
                LLM Grading
            </div>
            <div class="mode-badge {{ $report->usedGoldenSql ? 'active' : '' }}">
                @if($report->usedGoldenSql)
                    <span class="icon">&#10003;</span>
                @else
                    <span class="icon">-</span>
                @endif
                Golden SQL
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="card">
                <div class="card-label">Total Tests</div>
                <div class="card-value">{{ $report->getTotalTests() }}</div>
            </div>
            <div class="card">
                <div class="card-label">Pass Rate</div>
                <div class="card-value {{ $report->getPassRate() >= 80 ? 'pass' : ($report->getPassRate() >= 50 ? 'error' : 'fail') }}">
                    {{ number_format($report->getPassRate(), 1) }}%
                </div>
            </div>
            <div class="card">
                <div class="card-label">Passed / Failed / Errors</div>
                <div class="card-value">
                    <span class="pass">{{ $report->getPassedCount() }}</span>
                    /
                    <span class="fail">{{ $report->getFailedCount() }}</span>
                    /
                    <span class="error">{{ $report->getErrorCount() }}</span>
                </div>
            </div>
            <div class="card">
                <div class="card-label">Duration</div>
                <div class="card-value">{{ number_format($report->totalDuration, 2) }}s</div>
            </div>
            @if($report->usedLlmGrader && $report->getAverageLlmScore() !== null)
            <div class="card">
                <div class="card-label">Avg LLM Score</div>
                <div class="card-value">{{ number_format($report->getAverageLlmScore() * 100, 1) }}%</div>
            </div>
            @endif
        </div>

        <!-- Category Stats -->
        @php $categoryStats = $report->getCategoryStats(); @endphp
        @if(count($categoryStats) > 1)
        <div class="category-section">
            <h2 class="section-title">Results by Category</h2>
            <div class="category-grid">
                @foreach($categoryStats as $category => $stats)
                <div class="category-card">
                    <div class="category-name">{{ str_replace('_', ' ', $category) }}</div>
                    <div class="category-stats">
                        {{ $stats['passed'] }}/{{ $stats['total'] }} passed ({{ number_format($stats['pass_rate'], 0) }}%)
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ $stats['pass_rate'] }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Results Table -->
        <div class="results-section">
            <h2 class="section-title">All Results</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;">Status</th>
                            <th>Test Name</th>
                            <th>Category</th>
                            <th>Duration</th>
                            @if($report->usedLlmGrader)
                            <th>LLM Score</th>
                            @endif
                            @if($report->usedGoldenSql)
                            <th>Result Match</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report->results as $result)
                        <tr>
                            <td>
                                <span class="status-icon status-{{ $result->status }}">
                                    {{ $result->getStatusEmoji() }}
                                </span>
                            </td>
                            <td>{{ $result->testCaseName }}</td>
                            <td><span class="badge badge-category">{{ $result->category }}</span></td>
                            <td>{{ number_format($result->duration, 2) }}s</td>
                            @if($report->usedLlmGrader)
                            <td>
                                @if($result->gradeResult)
                                    {{ number_format($result->gradeResult->score * 100, 0) }}%
                                @else
                                    -
                                @endif
                            </td>
                            @endif
                            @if($report->usedGoldenSql)
                            <td>
                                @if($result->resultMatch === true)
                                    <span class="status-pass">&#10003;</span>
                                @elseif($result->resultMatch === false)
                                    <span class="status-fail">&#10007;</span>
                                @else
                                    -
                                @endif
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Failed Tests Detail -->
        @php $failedTests = $report->getFailedTests(); @endphp
        @if($failedTests->isNotEmpty())
        <div class="failed-section">
            <h2 class="section-title">Failed Test Details</h2>
            @foreach($failedTests as $result)
            <div class="failed-test {{ $result->isError() ? 'error' : '' }}">
                <div class="failed-test-header">
                    <span class="failed-test-name">
                        <span class="status-icon status-{{ $result->status }}">{{ $result->getStatusEmoji() }}</span>
                        {{ $result->testCaseName }}
                    </span>
                    <span class="badge badge-category">{{ $result->category }}</span>
                </div>
                <dl class="failed-test-content">
                    <dt>Question</dt>
                    <dd>{{ $result->question }}</dd>

                    @if($result->error)
                    <dt>Error</dt>
                    <dd class="code-block">{{ $result->error }}</dd>
                    @endif

                    @if(!empty($result->missingStrings))
                    <dt>Missing Expected Strings</dt>
                    <dd>{{ implode(', ', $result->missingStrings) }}</dd>
                    @endif

                    @if($result->gradeResult && !$result->gradeResult->passed)
                    <dt>LLM Grading</dt>
                    <dd>
                        Score: {{ number_format($result->gradeResult->score * 100, 0) }}% ({{ $result->gradeResult->getScoreLabel() }})<br>
                        {{ $result->gradeResult->reasoning }}
                    </dd>
                    @endif

                    @if($result->resultMatch === false && $result->resultExplanation)
                    <dt>Result Comparison</dt>
                    <dd>{{ $result->resultExplanation }}</dd>
                    @endif

                    @if($result->response)
                    <dt>Response</dt>
                    <dd class="code-block">{{ Str::limit($result->response, 500) }}</dd>
                    @endif

                    @if($result->sql)
                    <dt>SQL</dt>
                    <dd class="code-block">{{ $result->sql }}</dd>
                    @endif
                </dl>
            </div>
            @endforeach
        </div>
        @endif

        <div class="footer">
            Generated by SQL Agent Evaluation System
        </div>
    </div>
</body>
</html>

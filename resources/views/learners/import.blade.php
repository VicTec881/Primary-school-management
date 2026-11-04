@extends('layouts.app')

@section('title', 'Import Existing Learners')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-white d-flex align-items-center">
                <i class="bi bi-file-earmark-arrow-up-fill text-primary me-2 fs-5"></i>
                <h5 class="mb-0 fw-bold">Import Existing Learners from CSV</h5>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info small mb-4">
                    <h6 class="alert-heading fw-bold mb-1"><i class="bi bi-info-circle-fill me-1"></i>Import Specifications:</h6>
                    <ul class="mb-0 ps-3">
                        <li>File format must be standard comma-separated values (<code>.csv</code>).</li>
                        <li>Existing admission numbers are automatically detected to prevent duplicates.</li>
                        <li>If admission numbers are blank, the system automatically assigns sequential admission numbers.</li>
                        <li>Existing historical fee balances can be specified in the <code>fee_balance</code> column.</li>
                        <li>Invalid rows will be reported safely without interrupting valid entries.</li>
                    </ul>
                </div>

                <form action="{{ route('learners.import.post') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label for="csv_file" class="form-label fw-semibold">Select CSV File to Upload</label>
                        <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv,text/csv" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('learners.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                        <button type="submit" class="btn btn-primary fw-semibold px-4">
                            <i class="bi bi-upload me-1"></i> Upload and Import
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sample CSV Layout Card -->
        <div class="card">
            <div class="card-header bg-white fw-bold small text-uppercase">Sample CSV Structure (Copy or Export):</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm small mb-0 font-monospace">
                        <thead class="table-light">
                            <tr>
                                <th>admission_number</th>
                                <th>first_name</th>
                                <th>last_name</th>
                                <th>gender</th>
                                <th>dob</th>
                                <th>grade</th>
                                <th>stream</th>
                                <th>guardian_name</th>
                                <th>guardian_phone</th>
                                <th>fee_balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>KPS/2026/0010</td>
                                <td>Kevin</td>
                                <td>Ochieng</td>
                                <td>male</td>
                                <td>2019-04-12</td>
                                <td>Grade 1</td>
                                <td>Red</td>
                                <td>Peter Ochieng</td>
                                <td>0712345678</td>
                                <td>0.00</td>
                            </tr>
                            <tr>
                                <td>KPS/2026/0011</td>
                                <td>Stacy</td>
                                <td>Mutua</td>
                                <td>female</td>
                                <td>2018-09-25</td>
                                <td>Grade 2</td>
                                <td>Blue</td>
                                <td>Grace Mutua</td>
                                <td>0722334455</td>
                                <td>4500.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


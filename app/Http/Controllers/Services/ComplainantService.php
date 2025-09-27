<?php

namespace App\Http\Controllers\Services;

use App\Models\Complainant;

class ComplainantService
{
    /**
     * Create a new report
     */
    public function createReport(array $data): Complainant
    {
        return Complainant::create($data);
    }

    /**
     * Get all reports (for complainant history)
     */
    public function getReports()
    {
        return Complainant::orderBy('created_at', 'desc')->get();
    }

    /**
     * Get a single report
     */
    public function getReportById(int $id): ?Complainant
    {
        return Complainant::find($id);
    }

    /**
     * Update report status or details
     */
    public function updateReport(int $id, array $data): ?Complainant
    {
        $report = Complainant::find($id);
        if ($report) {
            $report->update($data);
        }
        return $report;
    }

    /**
     * Delete a report
     */
    public function deleteReport(int $id): bool
    {
        $report = Complainant::find($id);
        if ($report) {
            return (bool) $report->delete();
        }
        return false;
    }
}

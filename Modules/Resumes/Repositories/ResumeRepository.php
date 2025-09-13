<?php 

namespace Modules\Vacancies\Repositories;

use App\Models\Resume;
use Modules\Vacancies\Interfaces\ResumeInterface;

class ResumeRepository implements ResumeInterface
{
    public function store(array $data): Resume
    {
        // If no resume exists yet for this user, mark as primary
        if (! Resume::where('user_id', $data['user_id'])->exists()) {
            $data['is_primary'] = true;
        }

        $resume = Resume::create($data);

        // Ensure only one primary resume per user
        if (! empty($data['is_primary']) && $data['is_primary'] === true) {
            $this->setPrimary($resume);
        }

        return $resume;
    }

    public function update(Resume $resume, array $data): Resume
    {
        $resume->update($data);

        // If user explicitly updates is_primary to true, reset others
        if (! empty($data['is_primary']) && $data['is_primary'] === true) {
            $this->setPrimary($resume);
        }

        return $resume;
    }

    public function findById(int $id): ?Resume
    {
        return Resume::find($id);
    }

    public function delete(Resume $resume): bool
    {
        return $resume->delete();
    }

    /**
     * Ensure only one primary resume exists per user.
     */
    protected function setPrimary(Resume $resume): void
    {
        Resume::where('user_id', $resume->user_id)
            ->where('id', '!=', $resume->id)
            ->update(['is_primary' => false]);

        $resume->update(['is_primary' => true]);
    }
}

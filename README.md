# Inter-AI-HH-Data Platform

This project is a platform for companies to search for candidates from a large database. The platform provides advanced search capabilities, including AI-powered analysis of custom requirements.

## Modules

### HH (Head Hunter) Module

This module contains the core functionality for candidate search and analysis.

#### Key Components:

*   **Models**:
    *   `Candidate`: Represents a candidate from the Head Hunter database.
    *   `Company`: Represents a company using the platform.
    *   `SearchRequest`: Stores the search queries made by companies, including filters and custom text.
    *   `SearchResult`: Stores the results of a search, linking candidates to a search request with a match percentage.

*   **Migrations**:
    *   Database schemas for `companies`, `candidates`, `search_requests`, and `search_results` tables.

*   **Controllers**:
    *   `CandidateSearchController`: Handles the creation of search requests, dispatches the processing job, and displays the results.

*   **Jobs**:
    *   `ProcessCandidateSearch`: An asynchronous job that performs the candidate search and analysis in the background. It first filters candidates based on standard criteria and then uses a simulated AI analysis to calculate a match percentage for custom requirements.

*   **Routes**:
    *   Web routes for displaying the search form, handling form submission, showing a processing page, and displaying the final results. All routes are prefixed with `/hh`.

*   **Views**:
    *   `search/create.blade.php`: The form where companies can input their search criteria.
    *   `search/processing.blade.php`: A page shown to the user while their search is being processed in the background.
    *   `search/results.blade.php`: The page that displays the matched candidates and their match percentage.

#### Workflow:

1.  A company user fills out the search form with standard filters (e.g., experience, specialization) and detailed custom requirements.
2.  The `CandidateSearchController` creates a `SearchRequest` and dispatches the `ProcessCandidateSearch` job.
3.  The user is redirected to a "processing" page, informing them that the results will be available in their account.
4.  The `ProcessCandidateSearch` job runs in the background, filtering candidates and calculating a match score.
5.  The results are stored in the `search_results` table.
6.  The company can view the results on the results page, which shows a list of candidates ranked by their match percentage.

### Next Steps:

1.  **Setup Master Layout**: Create or configure the `hh::layouts.master` to provide a consistent UI.
2.  **Authentication & Authorization**: Implement proper user-company association and policies to ensure users can only see their own search results.
3.  **Database Seeding**: Create a seeder to populate the `candidates` table with the purchased Head Hunter data.
4.  **Queue Configuration**: Configure the application's queue driver (e.g., Redis, Database) and run the queue worker.
5.  **Real AI Integration**: Replace the simulated `calculateMatch` method in the job with a real AI/ML service API call.

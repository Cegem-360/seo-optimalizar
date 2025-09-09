<?php

namespace App\Console\Commands\Google;

use Illuminate\Console\Command;

class SetupWorkspaceDelegation extends Command
{
    protected $signature = 'google:setup-workspace-delegation';

    protected $description = 'Guide for setting up Google Workspace domain-wide delegation';

    public function handle(): int
    {
        $this->info('Google Workspace Domain-Wide Delegation Setup');
        $this->info('=============================================');
        $this->newLine();

        $this->comment('Step 1: Get Service Account Client ID');
        $this->line('1. Go to: https://console.cloud.google.com');
        $this->line('2. Navigate to: IAM & Admin → Service Accounts');
        $this->line('3. Click on your service account');
        $this->line('4. Copy the "Unique ID" (numeric Client ID)');
        $this->newLine();

        $this->comment('Step 2: Enable Domain-Wide Delegation');
        $this->line('1. In the service account details page');
        $this->line('2. Click "Show Domain-Wide Delegation"');
        $this->line('3. Check "Enable Google Workspace Domain-wide Delegation"');
        $this->line('4. Save');
        $this->newLine();

        $this->comment('Step 3: Configure in Google Workspace Admin');
        $this->line('1. Go to: https://admin.google.com');
        $this->line('2. Navigate to: Security → API controls → Domain-wide delegation');
        $this->line('3. Click "Add new"');
        $this->line('4. Enter:');
        $this->line('   - Client ID: [Your Service Account Unique ID]');
        $this->line('   - OAuth Scopes:');
        $this->line('     https://www.googleapis.com/auth/webmasters.readonly');
        $this->line('     https://www.googleapis.com/auth/webmasters');
        $this->line('5. Click "Authorize"');
        $this->newLine();

        $this->comment('Step 4: Configure impersonation in your app');
        $this->line('Update your GoogleSearchConsoleService to impersonate a user:');
        $this->newLine();

        $this->info('Example code modification needed:');
        $this->line('$this->googleClient->setSubject("user@yourdomain.com");');
        $this->line('// This allows the service account to act as this user');
        $this->newLine();

        $this->comment('Step 5: Grant Search Console access');
        $this->line('1. Go to: https://search.google.com/search-console');
        $this->line('2. For each property:');
        $this->line('   - Settings → Users and permissions');
        $this->line("   - Add the email you're impersonating (not the service account)");
        $this->line('   - Or add the service account email directly');
        $this->newLine();

        $this->info('Benefits of Domain-Wide Delegation:');
        $this->line('✓ Service account can access all users\' Search Console data');
        $this->line('✓ No need to add service account to each property individually');
        $this->line('✓ Can impersonate any user in your domain');
        $this->newLine();

        if ($this->confirm('Would you like to update the code for impersonation?')) {
            $email = $this->ask('Enter the email to impersonate (e.g., admin@yourdomain.com)');

            $this->info('Add this to your .env file:');
            $this->line('GOOGLE_WORKSPACE_SUBJECT="' . $email . '"');
            $this->newLine();

            $this->info('The GoogleSearchConsoleService will be updated to use this.');
        }

        return self::SUCCESS;
    }
}

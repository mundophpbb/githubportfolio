<?php
/**
 *
 * GitHub Portfolio. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 MundoPHPBB
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = [];
}

$lang = array_merge($lang, [
    'ACP_GITHUBPORTFOLIO'                              => 'GitHub Portfolio',
    'ACP_GITHUBPORTFOLIO_TITLE'                        => 'GitHub Portfolio settings',
    'ACP_GITHUBPORTFOLIO_SETTINGS'                     => 'Settings',
    'ACP_GITHUBPORTFOLIO_SETTINGS_EXPLAIN'             => 'Configure the GitHub user, cache, manual cache refresh, filters, card ordering, portal block, and optional forum discussion links.',
    'ACP_GITHUBPORTFOLIO_ENABLED'                      => 'Enable extension',
    'ACP_GITHUBPORTFOLIO_ENABLED_EXPLAIN'              => 'Globally enables or disables GitHub Portfolio output.',
    'ACP_GITHUBPORTFOLIO_USERNAME'                     => 'GitHub username',
    'ACP_GITHUBPORTFOLIO_USERNAME_EXPLAIN'             => 'Public GitHub user from which repositories will be fetched.',
    'ACP_GITHUBPORTFOLIO_TOKEN'                        => 'GitHub token (optional)',
    'ACP_GITHUBPORTFOLIO_TOKEN_EXPLAIN'                => 'Optional token to increase the API limit and avoid rate-limit problems.',
    'ACP_GITHUBPORTFOLIO_LIMIT'                        => 'Repository limit',
    'ACP_GITHUBPORTFOLIO_LIMIT_EXPLAIN'                => 'Maximum number of repositories displayed on the extension page.',
    'ACP_GITHUBPORTFOLIO_CACHE_TTL'                    => 'Cache time (seconds)',
    'ACP_GITHUBPORTFOLIO_CACHE_TTL_EXPLAIN'            => 'How long the repository list should remain cached.',
    'ACP_GITHUBPORTFOLIO_HIDE_FORKS'                   => 'Hide forks',
    'ACP_GITHUBPORTFOLIO_HIDE_FORKS_EXPLAIN'           => 'Do not display repositories marked as forks.',
    'ACP_GITHUBPORTFOLIO_HIDE_ARCHIVED'                => 'Hide archived repositories',
    'ACP_GITHUBPORTFOLIO_HIDE_ARCHIVED_EXPLAIN'        => 'Do not display repositories marked as archived.',
    'ACP_GITHUBPORTFOLIO_SORT_BY'                      => 'Sort by',
    'ACP_GITHUBPORTFOLIO_SORT_BY_EXPLAIN'              => 'Defines the card order when no manual repository filter is filled in.',
    'ACP_GITHUBPORTFOLIO_SORT_UPDATED'                 => 'Most recently updated',
    'ACP_GITHUBPORTFOLIO_SORT_STARS'                   => 'Most stars',
    'ACP_GITHUBPORTFOLIO_SORT_NAME'                    => 'Name',
    'ACP_GITHUBPORTFOLIO_MANUAL_ORDER'                 => 'Manual repository order',
    'ACP_GITHUBPORTFOLIO_MANUAL_ORDER_EXPLAIN'         => 'Optional. Enter repository names separated by commas or line breaks. They move to the top of the regular list in the exact order written here. Featured projects still stay above them.',
    'ACP_GITHUBPORTFOLIO_SELECTED_REPOS'               => 'Show only these repositories',
    'ACP_GITHUBPORTFOLIO_SELECTED_REPOS_EXPLAIN'       => 'Optional. Enter only the repositories that should be shown, separated by commas or line breaks. When filled, the typed order will be respected.',
    'ACP_GITHUBPORTFOLIO_HIDDEN_REPOS'                 => 'Hide these repositories',
    'ACP_GITHUBPORTFOLIO_HIDDEN_REPOS_EXPLAIN'         => 'Optional. Enter repository names separated by commas or line breaks. They will be hidden from both the page and the portal, even if they exist on GitHub.',
    'ACP_GITHUBPORTFOLIO_PORTAL_BLOCK'                 => 'Portal block',
    'ACP_GITHUBPORTFOLIO_SHOW_PORTAL_BLOCK'            => 'Show block in portal',
    'ACP_GITHUBPORTFOLIO_SHOW_PORTAL_BLOCK_EXPLAIN'    => 'Shows a GitHub projects section inside the Forum Portal.',
    'ACP_GITHUBPORTFOLIO_PORTAL_TITLE'                 => 'Portal block title',
    'ACP_GITHUBPORTFOLIO_PORTAL_TITLE_EXPLAIN'         => 'Leave blank to use the default title.',
    'ACP_GITHUBPORTFOLIO_PORTAL_LIMIT'                 => 'Number of cards in portal',
    'ACP_GITHUBPORTFOLIO_PORTAL_LIMIT_EXPLAIN'         => 'Maximum number of projects displayed in the portal block.',
    'ACP_GITHUBPORTFOLIO_FEATURED_TITLE'               => 'Featured title',
    'ACP_GITHUBPORTFOLIO_FEATURED_TITLE_EXPLAIN'       => 'Text shown above pinned featured projects. Leave blank to use the default.',
    'ACP_GITHUBPORTFOLIO_FEATURED_REPOS'               => 'Featured projects',
    'ACP_GITHUBPORTFOLIO_FEATURED_REPOS_EXPLAIN'       => 'Optional. Enter repository names separated by commas or line breaks. They appear first and receive a featured card on the portal and on the main page.',
    'ACP_GITHUBPORTFOLIO_DISCUSSION_LINKS'             => 'Discussion links',
    'ACP_GITHUBPORTFOLIO_REPO_DISCUSSIONS'             => 'Link repository to discussion',
    'ACP_GITHUBPORTFOLIO_REPO_DISCUSSIONS_EXPLAIN'     => 'One line per repository. Accepted formats: repo-name = 123, repo-name = viewtopic.php?f=2&t=10, or repo-name = https://yoursite.com/viewtopic.php?t=10. The Discussion button only appears when a mapping exists.',
    'ACP_GITHUBPORTFOLIO_REFRESH_CACHE'                => 'Refresh cache now',
    'ACP_GITHUBPORTFOLIO_REFRESH_CACHE_EXPLAIN'        => 'Use this button when something changes on GitHub and you want to see it right away without waiting for the normal cache time.',
    'ACP_GITHUBPORTFOLIO_CACHE_REFRESHED'              => 'GitHub Portfolio cache refreshed successfully.',

    'ACP_GITHUBPORTFOLIO_STATUS'                       => 'Current status',
    'ACP_GITHUBPORTFOLIO_STATUS_EXPLAIN'               => 'Quick summary of what is visible right now, including counts and useful verification links.',
    'ACP_GITHUBPORTFOLIO_STATUS_ITEM'                  => 'Item',
    'ACP_GITHUBPORTFOLIO_STATUS_VALUE'                 => 'Value',
    'ACP_GITHUBPORTFOLIO_STATUS_VISIBLE_PROJECTS'      => 'Visible projects',
    'ACP_GITHUBPORTFOLIO_STATUS_FEATURED_PROJECTS'     => 'Featured projects',
    'ACP_GITHUBPORTFOLIO_STATUS_REGULAR_PROJECTS'      => 'Regular projects',
    'ACP_GITHUBPORTFOLIO_STATUS_DISCUSSIONS'           => 'Linked discussions',
    'ACP_GITHUBPORTFOLIO_STATUS_LANGUAGES'             => 'Visible languages',
    'ACP_GITHUBPORTFOLIO_SYNC_REPO_COUNT'              => [1 => '%d repository', 2 => '%d repositories'],
    'ACP_GITHUBPORTFOLIO_STATUS_LAST_SYNC'             => 'Last sync',
    'ACP_GITHUBPORTFOLIO_STATUS_PUBLIC_PAGE'           => 'Public page',
    'ACP_GITHUBPORTFOLIO_STATUS_PROFILE'               => 'GitHub profile',
    'ACP_GITHUBPORTFOLIO_STATUS_OPEN_PAGE'             => 'Open projects page',
    'ACP_GITHUBPORTFOLIO_STATUS_QUICK_HELP'            => 'Quick guide',
    'ACP_GITHUBPORTFOLIO_STATUS_QUICK_HELP_EXPLAIN'    => 'Use these fields together to shape the showcase without needing to edit GitHub all the time.',
    'ACP_GITHUBPORTFOLIO_STATUS_HELP_FEATURED'         => 'Featured projects keeps the most important repositories pinned at the top.',
    'ACP_GITHUBPORTFOLIO_STATUS_HELP_SELECTED'         => 'Show only these repositories closes the showcase and displays only what you list.',
    'ACP_GITHUBPORTFOLIO_STATUS_HELP_HIDDEN'           => 'Hide these repositories removes specific projects from the page and the portal.',
    'ACP_GITHUBPORTFOLIO_STATUS_HELP_DISCUSSIONS'      => 'Link repository to discussion enables the badge and direct links to the forum.',

    'GITHUBPORTFOLIO_NAV'                              => 'Projects',
    'GITHUBPORTFOLIO_PAGE_TITLE'                       => 'GitHub Projects',
    'GITHUBPORTFOLIO_PAGE_EXPLAIN'                     => 'A cleaner showcase of your projects, integrated into phpBB without turning everything into loose forum topics.',
    'GITHUBPORTFOLIO_NO_REPOS'                         => 'No repositories are available right now. Check the ACP settings and cache.',
    'GITHUBPORTFOLIO_VIEW_ON_GITHUB'                   => 'View on GitHub',
    'GITHUBPORTFOLIO_VIEW_ALL'                         => 'View all',
    'GITHUBPORTFOLIO_VIEW_DISCUSSION'                  => 'Discussion',
    'GITHUBPORTFOLIO_LANGUAGE'                         => 'Language',
    'GITHUBPORTFOLIO_STARS'                            => 'Stars',
    'GITHUBPORTFOLIO_UPDATED'                          => 'Updated',
    'GITHUBPORTFOLIO_FORK'                             => 'Fork',
    'GITHUBPORTFOLIO_ARCHIVED'                         => 'Archived',
    'GITHUBPORTFOLIO_PORTAL_TITLE_DEFAULT'             => 'GitHub Projects',
    'GITHUBPORTFOLIO_PROFILE_LABEL'                    => 'Profile',
    'GITHUBPORTFOLIO_NO_DESCRIPTION'                   => 'No description provided on GitHub.',
    'GITHUBPORTFOLIO_FEATURED_TITLE_DEFAULT'          => 'Featured projects',
    'GITHUBPORTFOLIO_FEATURED_BADGE'                  => 'Featured',
    'GITHUBPORTFOLIO_METRIC_PROJECTS'                 => 'Projects',
    'GITHUBPORTFOLIO_METRIC_FEATURED'                 => 'Featured',
    'GITHUBPORTFOLIO_METRIC_SHOWCASE'                 => 'Showcase',
    'GITHUBPORTFOLIO_LAST_SYNC'                      => 'Last sync',
    'GITHUBPORTFOLIO_PROFILE_EXPLAIN'                 => 'Public GitHub repositories integrated into phpBB on a dedicated page and in the portal.',
    'GITHUBPORTFOLIO_PROFILE_EMPTY'                   => 'Set a username in the ACP to display the full portfolio here.',
    'GITHUBPORTFOLIO_EXPLORE_PROJECTS'                => 'Explore projects',
    'GITHUBPORTFOLIO_SECTION_FEATURED'                => 'Curated',
    'GITHUBPORTFOLIO_FEATURED_EXPLAIN'                => 'Your main projects stay at the top so the most important work is seen first.',
    'GITHUBPORTFOLIO_SECTION_CATALOG'                 => 'Catalog',
    'GITHUBPORTFOLIO_SECTION_ALL_PROJECTS'            => 'All projects',
    'GITHUBPORTFOLIO_CATALOG_EXPLAIN'                 => 'The remaining projects appear below in a cleaner layout that is easier to browse.',
    'GITHUBPORTFOLIO_FILTER_BY_LANGUAGE'              => 'Filter by language',
    'GITHUBPORTFOLIO_FILTER_ALL'                      => 'All',
    'GITHUBPORTFOLIO_FILTER_NO_LANGUAGE'              => 'No language',
    'GITHUBPORTFOLIO_FILTER_EMPTY'                    => 'No projects match this filter',
    'GITHUBPORTFOLIO_FILTER_EMPTY_EXPLAIN'            => 'Pick a different language or return to the full showcase view.',
]);

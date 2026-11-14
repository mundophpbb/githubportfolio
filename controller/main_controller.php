<?php
/**
 *
 * GitHub Portfolio. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 MundoPHPBB
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace mundophpbb\githubportfolio\controller;

use phpbb\template\template;
use phpbb\user;
use phpbb\controller\helper;
use mundophpbb\githubportfolio\service\github_api;
use phpbb\config\config;
use Symfony\Component\HttpFoundation\Response;

class main_controller
{
    protected template $template;
    protected user $user;
    protected helper $helper;
    protected github_api $github_api;
    protected config $config;

    public function __construct(template $template, user $user, helper $helper, github_api $github_api, config $config)
    {
        $this->template = $template;
        $this->user = $user;
        $this->helper = $helper;
        $this->github_api = $github_api;
        $this->config = $config;
    }

    public function handle(): Response
    {
        $all_repos = $this->github_api->get_repositories();
        $featured_repos = $this->github_api->get_featured_repositories($all_repos);
        $regular_repos = $this->github_api->get_regular_repositories($all_repos);
        $username = (string) ($this->config['mundophpbb_githubportfolio_username'] ?? '');

        foreach ($featured_repos as $repo)
        {
            $this->template->assign_block_vars('featured_repos', $this->build_template_row($repo));
        }

        foreach ($regular_repos as $repo)
        {
            $this->template->assign_block_vars('repos', $this->build_template_row($repo));
        }

        $language_filters = $this->build_language_filters($all_repos);
        foreach ($language_filters as $filter)
        {
            $this->template->assign_block_vars('language_filters', $filter);
        }

        $summary_metrics = $this->build_summary_metrics($all_repos);

        $featured_title = trim((string) ($this->config['mundophpbb_githubportfolio_featured_title'] ?? ''));
        if ($featured_title === '')
        {
            $featured_title = $this->user->lang('GITHUBPORTFOLIO_FEATURED_TITLE_DEFAULT');
        }

        $last_sync_timestamp = !empty($this->config['mundophpbb_githubportfolio_last_sync_time'])
            ? (int) $this->config['mundophpbb_githubportfolio_last_sync_time']
            : 0;
        $last_sync_count = isset($this->config['mundophpbb_githubportfolio_last_sync_count'])
            ? (int) $this->config['mundophpbb_githubportfolio_last_sync_count']
            : 0;

        $this->template->assign_vars([
            'S_HAS_REPOS'                            => !empty($all_repos),
            'S_HAS_FEATURED_REPOS'                   => !empty($featured_repos),
            'S_HAS_REGULAR_REPOS'                    => !empty($regular_repos),
            'S_GITHUBPORTFOLIO_HAS_LANGUAGE_FILTERS' => !empty($language_filters),
            'GITHUBPORTFOLIO_USERNAME'               => $username,
            'GITHUBPORTFOLIO_PROFILE_URL'            => $username !== '' ? 'https://github.com/' . rawurlencode($username) : '',
            'GITHUBPORTFOLIO_FEATURED_TITLE'         => $featured_title,
            'GITHUBPORTFOLIO_REPO_TOTAL'             => count($all_repos),
            'GITHUBPORTFOLIO_FEATURED_TOTAL'         => count($featured_repos),
            'GITHUBPORTFOLIO_REGULAR_TOTAL'          => count($regular_repos),
            'GITHUBPORTFOLIO_DISCUSSION_TOTAL'       => $summary_metrics['discussion_total'],
            'GITHUBPORTFOLIO_LANGUAGE_TOTAL'         => $summary_metrics['language_total'],
            'GITHUBPORTFOLIO_TOP_LANGUAGE'           => htmlspecialchars($summary_metrics['top_language'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'S_GITHUBPORTFOLIO_HAS_TOP_LANGUAGE'     => $summary_metrics['top_language'] !== '',
            'S_GITHUBPORTFOLIO_HAS_LAST_SYNC'        => $last_sync_timestamp > 0,
            'GITHUBPORTFOLIO_LAST_SYNC'              => $last_sync_timestamp > 0 ? $this->user->format_date($last_sync_timestamp) : '',
            'GITHUBPORTFOLIO_LAST_SYNC_COUNT'        => $last_sync_count,
        ]);

        $page_title = '</> ' . $this->user->lang('GITHUBPORTFOLIO_PAGE_TITLE');

        return $this->helper->render('githubportfolio_page.html', $page_title);
    }

    protected function build_template_row(array $repo): array
    {
        $updated_at = !empty($repo['updated_at']) ? strtotime($repo['updated_at']) : false;

        return [
            'NAME'            => htmlspecialchars((string) ($repo['name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'URL'             => htmlspecialchars((string) ($repo['html_url'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'DESCRIPTION'     => htmlspecialchars((string) ($repo['description'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'LANGUAGE'        => htmlspecialchars((string) ($repo['language'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'LANGUAGE_CLASS'  => $this->build_language_class((string) ($repo['language'] ?? '')),
            'STARS'           => (int) ($repo['stargazers_count'] ?? 0),
            'UPDATED_AT'      => $updated_at ? $this->user->format_date($updated_at) : '',
            'IS_FORK'         => !empty($repo['fork']),
            'IS_ARCHIVED'     => !empty($repo['archived']),
            'DISCUSSION_URL'  => htmlspecialchars((string) ($repo['discussion_url'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'HAS_DISCUSSION'  => !empty($repo['discussion_url']),
            'IS_FEATURED'     => !empty($repo['featured']),
        ];
    }


    protected function build_summary_metrics(array $repos): array
    {
        $discussion_total = 0;
        $language_counts = [];

        foreach ($repos as $repo)
        {
            if (!empty($repo['discussion_url']))
            {
                $discussion_total++;
            }

            $language = trim((string) ($repo['language'] ?? ''));
            if ($language === '')
            {
                continue;
            }

            if (!isset($language_counts[$language]))
            {
                $language_counts[$language] = 0;
            }

            $language_counts[$language]++;
        }

        $top_language = '';
        if (!empty($language_counts))
        {
            uksort($language_counts, 'strcasecmp');
            uasort($language_counts, static function (int $a, int $b): int
            {
                return $b <=> $a;
            });
            $top_language = (string) array_key_first($language_counts);
        }

        return [
            'discussion_total' => $discussion_total,
            'language_total'   => count($language_counts),
            'top_language'     => $top_language,
        ];
    }

    protected function build_language_filters(array $repos): array
    {
        $filters = [];

        foreach ($repos as $repo)
        {
            $language = trim((string) ($repo['language'] ?? ''));
            $key = $this->build_language_class($language);
            $label = $language !== '' ? $language : $this->user->lang('GITHUBPORTFOLIO_FILTER_NO_LANGUAGE');

            if (!isset($filters[$key]))
            {
                $filters[$key] = [
                    'KEY'   => $key,
                    'LABEL' => htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    'COUNT' => 0,
                ];
            }

            $filters[$key]['COUNT']++;
        }

        uasort($filters, static function (array $a, array $b): int
        {
            return strcasecmp((string) $a['LABEL'], (string) $b['LABEL']);
        });

        return array_values($filters);
    }

    protected function build_language_class(string $language): string
    {
        $language = strtolower(trim($language));
        if ($language === '')
        {
            return 'generic';
        }

        $language = preg_replace('/[^a-z0-9]+/', '-', $language);
        $language = trim((string) $language, '-');

        return $language !== '' ? $language : 'generic';
    }
}

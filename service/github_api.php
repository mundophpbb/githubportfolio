<?php
/**
 *
 * GitHub Portfolio. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 MundoPHPBB
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace mundophpbb\githubportfolio\service;

use phpbb\config\config;
use phpbb\cache\driver\driver_interface;

class github_api
{
    protected config $config;
    protected driver_interface $cache;

    public function __construct(config $config, driver_interface $cache)
    {
        $this->config = $config;
        $this->cache = $cache;
    }

    public function get_repositories(?int $limit_override = null): array
    {
        $repos = $this->get_all_repositories();

        if ($limit_override !== null)
        {
            $limit_override = max(1, min(50, $limit_override));
            return array_slice($repos, 0, $limit_override);
        }

        $default_limit = max(1, min(50, (int) ($this->config['mundophpbb_githubportfolio_limit'] ?? 6)));
        return array_slice($repos, 0, $default_limit);
    }

    public function get_all_repositories(): array
    {
        if (empty($this->config['mundophpbb_githubportfolio_enabled']))
        {
            return [];
        }

        $username = trim((string) ($this->config['mundophpbb_githubportfolio_username'] ?? ''));
        if ($username === '')
        {
            return [];
        }

        $cache_ttl = max(60, (int) ($this->config['mundophpbb_githubportfolio_cache_ttl'] ?? 1800));
        $hide_forks = !empty($this->config['mundophpbb_githubportfolio_hide_forks']);
        $hide_archived = !empty($this->config['mundophpbb_githubportfolio_hide_archived']);
        $token = trim((string) ($this->config['mundophpbb_githubportfolio_token'] ?? ''));
        $sort_by = $this->get_sort_by();
        $manual_order = $this->get_manual_order_repositories();
        $selected_repos = $this->get_selected_repositories();
        $hidden_repos = $this->get_hidden_repositories();
        $featured_repos = $this->get_featured_repositories_list();
        $cache_buster = (string) ($this->config['mundophpbb_githubportfolio_cache_buster'] ?? '0');

        $cache_key = '_mundophpbb_githubportfolio_' . md5(json_encode([
            $username,
            $hide_forks,
            $hide_archived,
            (bool) $token,
            $cache_buster,
        ]));

        $repos = $this->cache->get($cache_key);
        if ($repos === false || !is_array($repos))
        {
            $repos = $this->fetch_repositories($username, $token, $hide_forks, $hide_archived);
            $this->cache->put($cache_key, $repos, $cache_ttl);
        }

        $repos = $this->apply_hidden_repositories($repos, $hidden_repos);
        $repos = $this->apply_selected_repositories($repos, $selected_repos);
        $repos = $this->mark_featured_repositories($repos, $featured_repos);
        $repos = $this->sort_repositories($repos, $sort_by, !empty($selected_repos), !empty($featured_repos), $manual_order);
        $repos = $this->apply_discussion_mappings($repos);

        return array_values($repos);
    }

    public function get_featured_repositories(?array $repos = null): array
    {
        if ($repos === null)
        {
            $repos = $this->get_all_repositories();
        }

        $featured = [];
        foreach ($repos as $repo)
        {
            if (!empty($repo['featured']))
            {
                $featured[] = $repo;
            }
        }

        return $featured;
    }

    public function get_regular_repositories(?array $repos = null): array
    {
        if ($repos === null)
        {
            $repos = $this->get_all_repositories();
        }

        $regular = [];
        foreach ($repos as $repo)
        {
            if (empty($repo['featured']))
            {
                $regular[] = $repo;
            }
        }

        return $regular;
    }

    protected function fetch_repositories(string $username, string $token, bool $hide_forks, bool $hide_archived): array
    {
        $url = 'https://api.github.com/users/' . rawurlencode($username) . '/repos?sort=updated&direction=desc&per_page=100';
        $headers = [
            'User-Agent: phpBB-GitHubPortfolio',
            'Accept: application/vnd.github+json',
        ];

        if ($token !== '')
        {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $decoded = [];
        for ($page = 1; ; $page++)
        {
            $response = $this->http_get($url . '&page=' . $page, $headers);
            $batch = $response === null ? null : json_decode($response, true);
            if (!is_array($batch) || !array_is_list($batch))
            {
                return [];
            }

            foreach ($batch as $repository)
            {
                $decoded[] = $repository;
            }

            if (count($batch) < 100)
            {
                break;
            }
        }

        $repos = [];
        foreach ($decoded as $repo)
        {
            if (!is_array($repo) || empty($repo['name']) || empty($repo['html_url']))
            {
                continue;
            }

            if ($hide_forks && !empty($repo['fork']))
            {
                continue;
            }

            if ($hide_archived && !empty($repo['archived']))
            {
                continue;
            }

            $repos[] = [
                'name'              => (string) ($repo['name'] ?? ''),
                'html_url'          => (string) ($repo['html_url'] ?? ''),
                'description'       => trim((string) ($repo['description'] ?? '')),
                'language'          => (string) ($repo['language'] ?? ''),
                'stargazers_count'  => (int) ($repo['stargazers_count'] ?? 0),
                'updated_at'        => (string) ($repo['updated_at'] ?? ''),
                'fork'              => !empty($repo['fork']),
                'archived'          => !empty($repo['archived']),
                'discussion_url'    => '',
                'featured'          => false,
            ];
        }

        \set_config('mundophpbb_githubportfolio_last_sync_time', (string) time());
        \set_config('mundophpbb_githubportfolio_last_sync_count', (string) count($repos));

        return $repos;
    }

    protected function get_sort_by(): string
    {
        $sort_by = isset($this->config['mundophpbb_githubportfolio_sort_by'])
            ? (string) $this->config['mundophpbb_githubportfolio_sort_by']
            : 'updated';

        if (!in_array($sort_by, ['updated', 'stars', 'name'], true))
        {
            $sort_by = 'updated';
        }

        return $sort_by;
    }

    protected function get_manual_order_repositories(): array
    {
        $raw = isset($this->config['mundophpbb_githubportfolio_manual_order'])
            ? (string) $this->config['mundophpbb_githubportfolio_manual_order']
            : '';

        if ($raw === '')
        {
            return [];
        }

        return $this->parse_name_list($raw);
    }

    protected function get_selected_repositories(): array
    {
        $raw = isset($this->config['mundophpbb_githubportfolio_selected_repos'])
            ? (string) $this->config['mundophpbb_githubportfolio_selected_repos']
            : '';

        if ($raw === '')
        {
            return [];
        }

        return $this->parse_name_list($raw);
    }

    protected function get_featured_repositories_list(): array
    {
        $raw = isset($this->config['mundophpbb_githubportfolio_featured_repos'])
            ? (string) $this->config['mundophpbb_githubportfolio_featured_repos']
            : '';

        if ($raw === '')
        {
            return [];
        }

        return $this->parse_name_list($raw);
    }

    protected function get_hidden_repositories(): array
    {
        $raw = isset($this->config['mundophpbb_githubportfolio_hidden_repos'])
            ? (string) $this->config['mundophpbb_githubportfolio_hidden_repos']
            : '';

        if ($raw === '')
        {
            return [];
        }

        return $this->parse_name_list($raw);
    }

    protected function parse_name_list(string $raw): array
    {
        $parts = preg_split('/[\r\n,;]+/', $raw);
        $repos = [];

        foreach ($parts as $part)
        {
            $name = trim((string) $part);
            if ($name === '')
            {
                continue;
            }

            $repos[strtolower($name)] = $name;
        }

        return array_values($repos);
    }

    protected function apply_selected_repositories(array $repos, array $selected_repos): array
    {
        if (empty($selected_repos))
        {
            return $repos;
        }

        $indexed = [];
        foreach ($repos as $repo)
        {
            if (empty($repo['name']))
            {
                continue;
            }

            $indexed[strtolower((string) $repo['name'])] = $repo;
        }

        $selected = [];
        foreach ($selected_repos as $repo_name)
        {
            $key = strtolower((string) $repo_name);
            if (isset($indexed[$key]))
            {
                $selected[] = $indexed[$key];
            }
        }

        return $selected;
    }

    protected function apply_hidden_repositories(array $repos, array $hidden_repos): array
    {
        if (empty($hidden_repos))
        {
            return $repos;
        }

        $hidden_keys = array_flip(array_map('strtolower', $hidden_repos));

        $visible = [];
        foreach ($repos as $repo)
        {
            $name = strtolower((string) ($repo['name'] ?? ''));
            if ($name === '' || isset($hidden_keys[$name]))
            {
                continue;
            }

            $visible[] = $repo;
        }

        return $visible;
    }

    protected function mark_featured_repositories(array $repos, array $featured_repos): array
    {
        if (empty($featured_repos))
        {
            return $repos;
        }

        $featured_keys = array_flip(array_map('strtolower', $featured_repos));

        foreach ($repos as $index => $repo)
        {
            $name = strtolower((string) ($repo['name'] ?? ''));
            $repos[$index]['featured'] = isset($featured_keys[$name]);
        }

        return $repos;
    }

    protected function sort_repositories(array $repos, string $sort_by, bool $selected_order_locked = false, bool $featured_order_locked = false, array $manual_order = []): array
    {
        if ($selected_order_locked)
        {
            return $repos;
        }

        $featured_positions = [];
        if ($featured_order_locked)
        {
            $featured_positions = array_flip(array_map('strtolower', $this->get_featured_repositories_list()));
        }

        $manual_positions = [];
        if (!empty($manual_order))
        {
            $manual_positions = array_flip(array_map('strtolower', $manual_order));
        }

        usort($repos, static function ($a, $b) use ($sort_by, $featured_positions, $manual_positions)
        {
            $a_featured = !empty($a['featured']);
            $b_featured = !empty($b['featured']);

            if ($a_featured !== $b_featured)
            {
                return $a_featured ? -1 : 1;
            }

            if ($a_featured && $b_featured && !empty($featured_positions))
            {
                $a_pos = $featured_positions[strtolower((string) ($a['name'] ?? ''))] ?? PHP_INT_MAX;
                $b_pos = $featured_positions[strtolower((string) ($b['name'] ?? ''))] ?? PHP_INT_MAX;
                if ($a_pos !== $b_pos)
                {
                    return $a_pos <=> $b_pos;
                }
            }

            if (!$a_featured && !$b_featured && !empty($manual_positions))
            {
                $a_key = strtolower((string) ($a['name'] ?? ''));
                $b_key = strtolower((string) ($b['name'] ?? ''));
                $a_manual = array_key_exists($a_key, $manual_positions);
                $b_manual = array_key_exists($b_key, $manual_positions);

                if ($a_manual !== $b_manual)
                {
                    return $a_manual ? -1 : 1;
                }

                if ($a_manual && $b_manual)
                {
                    $a_pos = $manual_positions[$a_key] ?? PHP_INT_MAX;
                    $b_pos = $manual_positions[$b_key] ?? PHP_INT_MAX;
                    if ($a_pos !== $b_pos)
                    {
                        return $a_pos <=> $b_pos;
                    }
                }
            }

            switch ($sort_by)
            {
                case 'name':
                    return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));

                case 'stars':
                    $star_comparison = ((int) ($b['stargazers_count'] ?? 0)) <=> ((int) ($a['stargazers_count'] ?? 0));
                    if ($star_comparison !== 0)
                    {
                        return $star_comparison;
                    }
                    return strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? ''));

                case 'updated':
                default:
                    return strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? ''));
            }
        });

        return $repos;
    }

    protected function apply_discussion_mappings(array $repos): array
    {
        $discussion_map = $this->get_discussion_map();
        if (empty($discussion_map))
        {
            return $repos;
        }

        foreach ($repos as $index => $repo)
        {
            $repo_name = strtolower((string) ($repo['name'] ?? ''));
            if ($repo_name === '' || !isset($discussion_map[$repo_name]))
            {
                continue;
            }

            $repos[$index]['discussion_url'] = $discussion_map[$repo_name];
        }

        return $repos;
    }

    protected function get_discussion_map(): array
    {
        $raw = isset($this->config['mundophpbb_githubportfolio_repo_discussions'])
            ? trim((string) $this->config['mundophpbb_githubportfolio_repo_discussions'])
            : '';

        if ($raw === '')
        {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $map = [];

        foreach ($lines as $line)
        {
            $line = trim((string) $line);
            if ($line === '' || str_starts_with($line, '#'))
            {
                continue;
            }

            if (!preg_match('/^([^=|:]+?)\s*(?:=|\||:)\s*(.+)$/', $line, $matches))
            {
                continue;
            }

            $repo_name = strtolower(trim((string) $matches[1]));
            $target = $this->normalize_discussion_target((string) $matches[2]);
            if ($repo_name === '' || $target === '')
            {
                continue;
            }

            $map[$repo_name] = $target;
        }

        return $map;
    }

    protected function normalize_discussion_target(string $target): string
    {
        $target = trim($target);
        if ($target === '')
        {
            return '';
        }

        if (preg_match('#^https?://#i', $target))
        {
            return $target;
        }

        if (ctype_digit($target))
        {
            $target = 'viewtopic.php?t=' . (int) $target;
        }

        $board_url = $this->get_board_url();
        if ($board_url === '')
        {
            return $target;
        }

        return rtrim($board_url, '/') . '/' . ltrim($target, '/');
    }

    protected function get_board_url(): string
    {
        $protocol = (string) ($this->config['server_protocol'] ?? 'http://');
        $server_name = trim((string) ($this->config['server_name'] ?? ''));
        $server_port = (int) ($this->config['server_port'] ?? 80);
        $script_path = trim((string) ($this->config['script_path'] ?? ''));

        if ($server_name === '')
        {
            return '';
        }

        $port_suffix = '';
        if (($protocol === 'http://' && $server_port && $server_port !== 80) || ($protocol === 'https://' && $server_port && $server_port !== 443))
        {
            $port_suffix = ':' . $server_port;
        }

        $path = trim($script_path, '/');
        $path = $path !== '' ? '/' . $path : '';

        return rtrim($protocol . $server_name . $port_suffix . $path, '/');
    }

    protected function http_get(string $url, array $headers): ?string
    {
        if (function_exists('curl_init'))
        {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            $result = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status >= 200 && $status < 300 && is_string($result))
            {
                return $result;
            }
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 10,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);
        return is_string($result) ? $result : null;
    }
}

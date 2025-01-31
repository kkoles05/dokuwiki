<?php

namespace dokuwiki\Remote;

use Doku_Renderer_xhtml;
use dokuwiki\ChangeLog\MediaChangeLog;
use dokuwiki\ChangeLog\PageChangeLog;
use dokuwiki\Extension\AuthPlugin;
use dokuwiki\Extension\Event;
use dokuwiki\Utf8\Sort;

define('DOKU_API_VERSION', 11);

/**
 * Provides the core methods for the remote API.
 * The methods are ordered in 'wiki.<method>' and 'dokuwiki.<method>' namespaces
 */
class ApiCore
{
    /** @var int Increased whenever the API is changed */
    public const API_VERSION = 11;


    /** @var Api */
    private $api;

    /**
     * @param Api $api
     */
    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * Returns details about the core methods
     *
     * @return array
     */
    public function getRemoteInfo()
    {
        return [
            'dokuwiki.getVersion' => [
                'args' => [],
                'return' => 'string',
                'doc' => 'Returns the running DokuWiki version.'
            ],
            'dokuwiki.login' => [
                'args' => ['string', 'string'],
                'return' => 'int',
                'doc' => 'Tries to login with the given credentials and sets auth cookies.',
                'public' => '1'
            ],
            'dokuwiki.logoff' => [
                'args' => [],
                'return' => 'int',
                'doc' => 'Tries to logoff by expiring auth cookies and the associated PHP session.'
            ],
            'dokuwiki.getPagelist' => [
                'args' => ['string', 'array'],
                'return' => 'array',
                'doc' => 'List all pages within the given namespace.',
                'name' => 'readNamespace'
            ],
            'dokuwiki.search' => [
                'args' => ['string'],
                'return' => 'array',
                'doc' => 'Perform a fulltext search and return a list of matching pages'
            ],
            'dokuwiki.getTime' => [
                'args' => [],
                'return' => 'int',
                'doc' => 'Returns the current time at the remote wiki server as Unix timestamp.'
            ],
            'dokuwiki.setLocks' => [
                'args' => ['array'],
                'return' => 'array',
                'doc' => 'Lock or unlock pages.'
            ],
            'dokuwiki.getTitle' => [
                'args' => [],
                'return' => 'string',
                'doc' => 'Returns the wiki title.',
                'public' => '1'
            ],
            'dokuwiki.appendPage' => [
                'args' => ['string', 'string', 'array'],
                'return' => 'bool',
                'doc' => 'Append text to a wiki page.'
            ],
            'dokuwiki.createUser' => [
                'args' => ['struct'],
                'return' => 'bool',
                'doc' => 'Create a user. The result is boolean'
            ],
            'dokuwiki.deleteUsers' => [
                'args' => ['array'],
                'return' => 'bool',
                'doc' => 'Remove one or more users from the list of registered users.'
            ],
            'wiki.getPage' => [
                'args' => ['string'],
                'return' => 'string',
                'doc' => 'Get the raw Wiki text of page, latest version.',
                'name' => 'rawPage'
            ],
            'wiki.getPageVersion' => [
                'args' => ['string', 'int'],
                'name' => 'rawPage',
                'return' => 'string',
                'doc' => 'Return a raw wiki page'
            ],
            'wiki.getPageHTML' => [
                'args' => ['string'],
                'return' => 'string',
                'doc' => 'Return page in rendered HTML, latest version.',
                'name' => 'htmlPage'
            ],
            'wiki.getPageHTMLVersion' => [
                'args' => ['string', 'int'],
                'return' => 'string',
                'doc' => 'Return page in rendered HTML.',
                'name' => 'htmlPage'
            ],
            'wiki.getAllPages' => [
                'args' => [],
                'return' => 'array',
                'doc' => 'Returns a list of all pages. The result is an array of utf8 pagenames.',
                'name' => 'listPages'
            ],
            'wiki.getAttachments' => [
                'args' => ['string', 'array'],
                'return' => 'array',
                'doc' => 'Returns a list of all media files.',
                'name' => 'listAttachments'
            ],
            'wiki.getBackLinks' => [
                'args' => ['string'],
                'return' => 'array',
                'doc' => 'Returns the pages that link to this page.',
                'name' => 'listBackLinks'
            ],
            'wiki.getPageInfo' => [
                'args' => ['string'],
                'return' => 'array',
                'doc' => 'Returns a struct with info about the page, latest version.',
                'name' => 'pageInfo'
            ],
            'wiki.getPageInfoVersion' => [
                'args' => ['string', 'int'],
                'return' => 'array',
                'doc' => 'Returns a struct with info about the page.',
                'name' => 'pageInfo'
            ],
            'wiki.getPageVersions' => [
                'args' => ['string', 'int'],
                'return' => 'array',
                'doc' => 'Returns the available revisions of the page.',
                'name' => 'pageVersions'
            ],
            'wiki.putPage' => [
                'args' => ['string', 'string', 'array'],
                'return' => 'bool',
                'doc' => 'Saves a wiki page.'
            ],
            'wiki.listLinks' => [
                'args' => ['string'],
                'return' => 'array',
                'doc' => 'Lists all links contained in a wiki page.'
            ],
            'wiki.getRecentChanges' => [
                'args' => ['int'],
                'return' => 'array',
                'doc' => 'Returns a struct about all recent changes since given timestamp.'
            ],
            'wiki.getRecentMediaChanges' => [
                'args' => ['int'],
                'return' => 'array',
                'doc' => 'Returns a struct about all recent media changes since given timestamp.'
            ],
            'wiki.aclCheck' => ['args' => ['string', 'string', 'array'],
                'return' => 'int',
                'doc' => 'Returns the permissions of a given wiki page. By default, for current user/groups'
            ],
            'wiki.putAttachment' => ['args' => ['string', 'file', 'array'],
                'return' => 'array',
                'doc' => 'Upload a file to the wiki.'
            ],
            'wiki.deleteAttachment' => [
                'args' => ['string'],
                'return' => 'int',
                'doc' => 'Delete a file from the wiki.'
            ],
            'wiki.getAttachment' => [
                'args' => ['string'],
                'doc' => 'Return a media file',
                'return' => 'file',
                'name' => 'getAttachment'
            ],
            'wiki.getAttachmentInfo' => [
                'args' => ['string'],
                'return' => 'array',
                'doc' => 'Returns a struct with info about the attachment.'
            ],
            'dokuwiki.getXMLRPCAPIVersion' => [
                'args' => [],
                'name' => 'getAPIVersion',
                'return' => 'int',
                'doc' => 'Returns the XMLRPC API version.',
                'public' => '1'
            ],
            'wiki.getRPCVersionSupported' => [
                'args' => [],
                'name' => 'wikiRpcVersion',
                'return' => 'int',
                'doc' => 'Returns 2 with the supported RPC API version.',
                'public' => '1']
        ];
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return getVersion();
    }

    /**
     * @return int unix timestamp
     */
    public function getTime()
    {
        return time();
    }

    /**
     * Return a raw wiki page
     *
     * @param string $id wiki page id
     * @param int|string $rev revision timestamp of the page or empty string
     * @return string page text.
     * @throws AccessDeniedException if no permission for page
     */
    public function rawPage($id, $rev = '')
    {
        $id = $this->resolvePageId($id);
        if (auth_quickaclcheck($id) < AUTH_READ) {
            throw new AccessDeniedException('You are not allowed to read this file', 111);
        }
        $text = rawWiki($id, $rev);
        if (!$text) {
            return pageTemplate($id);
        } else {
            return $text;
        }
    }

    /**
     * Return a media file
     *
     * @param string $id file id
     * @return mixed media file
     * @throws AccessDeniedException no permission for media
     * @throws RemoteException not exist
     * @author Gina Haeussge <osd@foosel.net>
     *
     */
    public function getAttachment($id)
    {
        $id = cleanID($id);
        if (auth_quickaclcheck(getNS($id) . ':*') < AUTH_READ) {
            throw new AccessDeniedException('You are not allowed to read this file', 211);
        }

        $file = mediaFN($id);
        if (!@ file_exists($file)) {
            throw new RemoteException('The requested file does not exist', 221);
        }

        $data = io_readFile($file, false);
        return $this->api->toFile($data);
    }

    /**
     * Return info about a media file
     *
     * @param string $id page id
     * @return array
     * @author Gina Haeussge <osd@foosel.net>
     *
     */
    public function getAttachmentInfo($id)
    {
        $id = cleanID($id);
        $info = ['lastModified' => $this->api->toDate(0), 'size' => 0];

        $file = mediaFN($id);
        if (auth_quickaclcheck(getNS($id) . ':*') >= AUTH_READ) {
            if (file_exists($file)) {
                $info['lastModified'] = $this->api->toDate(filemtime($file));
                $info['size'] = filesize($file);
            } else {
                //Is it deleted media with changelog?
                $medialog = new MediaChangeLog($id);
                $revisions = $medialog->getRevisions(0, 1);
                if (!empty($revisions)) {
                    $info['lastModified'] = $this->api->toDate($revisions[0]);
                }
            }
        }

        return $info;
    }

    /**
     * Return a wiki page rendered to html
     *
     * @param string $id page id
     * @param string|int $rev revision timestamp or empty string
     * @return null|string html
     * @throws AccessDeniedException no access to page
     */
    public function htmlPage($id, $rev = '')
    {
        $id = $this->resolvePageId($id);
        if (auth_quickaclcheck($id) < AUTH_READ) {
            throw new AccessDeniedException('You are not allowed to read this page', 111);
        }
        return p_wiki_xhtml($id, $rev, false);
    }

    /**
     * List all pages - we use the indexer list here
     *
     * @return array
     */
    public function listPages()
    {
        $list = [];
        $pages = idx_get_indexer()->getPages();
        $pages = array_filter(array_filter($pages, 'isVisiblePage'), 'page_exists');
        Sort::ksort($pages);

        foreach (array_keys($pages) as $idx) {
            $perm = auth_quickaclcheck($pages[$idx]);
            if ($perm < AUTH_READ) {
                continue;
            }
            $page = [];
            $page['id'] = trim($pages[$idx]);
            $page['perms'] = $perm;
            $page['size'] = @filesize(wikiFN($pages[$idx]));
            $page['lastModified'] = $this->api->toDate(@filemtime(wikiFN($pages[$idx])));
            $list[] = $page;
        }

        return $list;
    }

    /**
     * List all pages in the given namespace (and below)
     *
     * @param string $ns
     * @param array $opts
     *    $opts['depth']   recursion level, 0 for all
     *    $opts['hash']    do md5 sum of content?
     * @return array
     */
    public function readNamespace($ns, $opts = [])
    {
        global $conf;

        if (!is_array($opts)) $opts = [];

        $ns = cleanID($ns);
        $dir = utf8_encodeFN(str_replace(':', '/', $ns));
        $data = [];
        $opts['skipacl'] = 0; // no ACL skipping for XMLRPC
        search($data, $conf['datadir'], 'search_allpages', $opts, $dir);
        return $data;
    }

    /**
     * List all pages in the given namespace (and below)
     *
     * @param string $query
     * @return array
     */
    public function search($query)
    {
        $regex = [];
        $data = ft_pageSearch($query, $regex);
        $pages = [];

        // prepare additional data
        $idx = 0;
        foreach ($data as $id => $score) {
            $file = wikiFN($id);

            if ($idx < FT_SNIPPET_NUMBER) {
                $snippet = ft_snippet($id, $regex);
                $idx++;
            } else {
                $snippet = '';
            }

            $pages[] = [
                'id' => $id,
                'score' => (int)$score,
                'rev' => filemtime($file),
                'mtime' => filemtime($file),
                'size' => filesize($file),
                'snippet' => $snippet,
                'title' => useHeading('navigation') ? p_get_first_heading($id) : $id
            ];
        }
        return $pages;
    }

    /**
     * Returns the wiki title.
     *
     * @return string
     */
    public function getTitle()
    {
        global $conf;
        return $conf['title'];
    }

    /**
     * List all media files.
     *
     * Available options are 'recursive' for also including the subnamespaces
     * in the listing, and 'pattern' for filtering the returned files against
     * a regular expression matching their name.
     *
     * @param string $ns
     * @param array $options
     *   $options['depth']     recursion level, 0 for all
     *   $options['showmsg']   shows message if invalid media id is used
     *   $options['pattern']   check given pattern
     *   $options['hash']      add hashes to result list
     * @return array
     * @throws AccessDeniedException no access to the media files
     * @author Gina Haeussge <osd@foosel.net>
     *
     */
    public function listAttachments($ns, $options = [])
    {
        global $conf;

        $ns = cleanID($ns);

        if (!is_array($options)) $options = [];
        $options['skipacl'] = 0; // no ACL skipping for XMLRPC

        if (auth_quickaclcheck($ns . ':*') >= AUTH_READ) {
            $dir = utf8_encodeFN(str_replace(':', '/', $ns));

            $data = [];
            search($data, $conf['mediadir'], 'search_media', $options, $dir);
            $len = count($data);
            if (!$len) return [];

            for ($i = 0; $i < $len; $i++) {
                unset($data[$i]['meta']);
                $data[$i]['perms'] = $data[$i]['perm'];
                unset($data[$i]['perm']);
                $data[$i]['lastModified'] = $this->api->toDate($data[$i]['mtime']);
            }
            return $data;
        } else {
            throw new AccessDeniedException('You are not allowed to list media files.', 215);
        }
    }

    /**
     * Return a list of backlinks
     *
     * @param string $id page id
     * @return array
     */
    public function listBackLinks($id)
    {
        return ft_backlinks($this->resolvePageId($id));
    }

    /**
     * Return some basic data about a page
     *
     * @param string $id page id
     * @param string|int $rev revision timestamp or empty string
     * @return array
     * @throws AccessDeniedException no access for page
     * @throws RemoteException page not exist
     */
    public function pageInfo($id, $rev = '')
    {
        $id = $this->resolvePageId($id);
        if (auth_quickaclcheck($id) < AUTH_READ) {
            throw new AccessDeniedException('You are not allowed to read this page', 111);
        }
        $file = wikiFN($id, $rev);
        $time = @filemtime($file);
        if (!$time) {
            throw new RemoteException('The requested page does not exist', 121);
        }

        // set revision to current version if empty, use revision otherwise
        // as the timestamps of old files are not necessarily correct
        if ($rev === '') {
            $rev = $time;
        }

        $pagelog = new PageChangeLog($id, 1024);
        $info = $pagelog->getRevisionInfo($rev);

        $data = [
            'name' => $id,
            'lastModified' => $this->api->toDate($rev),
            'author' => is_array($info) ? ($info['user'] ?: $info['ip']) : null,
            'version' => $rev
        ];

        return ($data);
    }

    /**
     * Save a wiki page
     *
     * @param string $id page id
     * @param string $text wiki text
     * @param array $params parameters: summary, minor edit
     * @return bool
     * @throws AccessDeniedException no write access for page
     * @throws RemoteException no id, empty new page or locked
     * @author Michael Klier <chi@chimeric.de>
     *
     */
    public function putPage($id, $text, $params = [])
    {
        global $TEXT;
        global $lang;

        $id = $this->resolvePageId($id);
        $TEXT = cleanText($text);
        $sum = $params['sum'];
        $minor = $params['minor'];

        if (empty($id)) {
            throw new RemoteException('Empty page ID', 131);
        }

        if (!page_exists($id) && trim($TEXT) == '') {
            throw new RemoteException('Refusing to write an empty new wiki page', 132);
        }

        if (auth_quickaclcheck($id) < AUTH_EDIT) {
            throw new AccessDeniedException('You are not allowed to edit this page', 112);
        }

        // Check, if page is locked
        if (checklock($id)) {
            throw new RemoteException('The page is currently locked', 133);
        }

        // SPAM check
        if (checkwordblock()) {
            throw new RemoteException('Positive wordblock check', 134);
        }

        // autoset summary on new pages
        if (!page_exists($id) && empty($sum)) {
            $sum = $lang['created'];
        }

        // autoset summary on deleted pages
        if (page_exists($id) && empty($TEXT) && empty($sum)) {
            $sum = $lang['deleted'];
        }

        lock($id);

        saveWikiText($id, $TEXT, $sum, $minor);

        unlock($id);

        // run the indexer if page wasn't indexed yet
        idx_addPage($id);

        return true;
    }

    /**
     * Appends text to a wiki page.
     *
     * @param string $id page id
     * @param string $text wiki text
     * @param array $params such as summary,minor
     * @return bool|string
     * @throws RemoteException
     */
    public function appendPage($id, $text, $params = [])
    {
        $currentpage = $this->rawPage($id);
        if (!is_string($currentpage)) {
            return $currentpage;
        }
        return $this->putPage($id, $currentpage . $text, $params);
    }

    /**
     * Create one or more users
     *
     * @param array[] $userStruct User struct
     *
     * @return boolean Create state
     *
     * @throws AccessDeniedException
     * @throws RemoteException
     */
    public function createUser($userStruct)
    {
        if (!auth_isadmin()) {
            throw new AccessDeniedException('Only admins are allowed to create users', 114);
        }

        /** @var AuthPlugin $auth */
        global $auth;

        if (!$auth->canDo('addUser')) {
            throw new AccessDeniedException(
                sprintf('Authentication backend %s can\'t do addUser', $auth->getPluginName()),
                114
            );
        }

        $user = trim($auth->cleanUser($userStruct['user'] ?? ''));
        $password = $userStruct['password'] ?? '';
        $name = trim(preg_replace('/[\x00-\x1f:<>&%,;]+/', '', $userStruct['name'] ?? ''));
        $mail = trim(preg_replace('/[\x00-\x1f:<>&%,;]+/', '', $userStruct['mail'] ?? ''));
        $groups = $userStruct['groups'] ?? [];

        $notify = (bool)$userStruct['notify'] ?? false;

        if ($user === '') throw new RemoteException('empty or invalid user', 401);
        if ($name === '') throw new RemoteException('empty or invalid user name', 402);
        if (!mail_isvalid($mail)) throw new RemoteException('empty or invalid mail address', 403);

        if ((string)$password === '') {
            $password = auth_pwgen($user);
        }

        if (!is_array($groups) || $groups === []) {
            $groups = null;
        }

        $ok = $auth->triggerUserMod('create', [$user, $password, $name, $mail, $groups]);

        if ($ok !== false && $ok !== null) {
            $ok = true;
        }

        if ($ok) {
            if ($notify) {
                auth_sendPassword($user, $password);
            }
        }

        return $ok;
    }


    /**
     * Remove one or more users from the list of registered users
     *
     * @param string[] $usernames List of usernames to remove
     *
     * @return bool
     *
     * @throws AccessDeniedException
     */
    public function deleteUsers($usernames)
    {
        if (!auth_isadmin()) {
            throw new AccessDeniedException('Only admins are allowed to delete users', 114);
        }
        /** @var AuthPlugin $auth */
        global $auth;
        return (bool)$auth->triggerUserMod('delete', [$usernames]);
    }

    /**
     * Uploads a file to the wiki.
     *
     * Michael Klier <chi@chimeric.de>
     *
     * @param string $id page id
     * @param string $file
     * @param array $params such as overwrite
     * @return false|string
     * @throws RemoteException
     */
    public function putAttachment($id, $file, $params = [])
    {
        $id = cleanID($id);
        $auth = auth_quickaclcheck(getNS($id) . ':*');

        if (!isset($id)) {
            throw new RemoteException('Filename not given.', 231);
        }

        global $conf;

        $ftmp = $conf['tmpdir'] . '/' . md5($id . clientIP());

        // save temporary file
        @unlink($ftmp);
        io_saveFile($ftmp, $file);

        $res = media_save(['name' => $ftmp], $id, $params['ow'], $auth, 'rename');
        if (is_array($res)) {
            throw new RemoteException($res[0], -$res[1]);
        } else {
            return $res;
        }
    }

    /**
     * Deletes a file from the wiki.
     *
     * @param string $id page id
     * @return int
     * @throws AccessDeniedException no permissions
     * @throws RemoteException file in use or not deleted
     * @author Gina Haeussge <osd@foosel.net>
     *
     */
    public function deleteAttachment($id)
    {
        $id = cleanID($id);
        $auth = auth_quickaclcheck(getNS($id) . ':*');
        $res = media_delete($id, $auth);
        if ($res & DOKU_MEDIA_DELETED) {
            return 0;
        } elseif ($res & DOKU_MEDIA_NOT_AUTH) {
            throw new AccessDeniedException('You don\'t have permissions to delete files.', 212);
        } elseif ($res & DOKU_MEDIA_INUSE) {
            throw new RemoteException('File is still referenced', 232);
        } else {
            throw new RemoteException('Could not delete file', 233);
        }
    }

    /**
     * Returns the permissions of a given wiki page for the current user or another user
     *
     * @param string $id page id
     * @param string|null $user username
     * @param array|null $groups array of groups
     * @return int permission level
     */
    public function aclCheck($id, $user = null, $groups = null)
    {
        /** @var AuthPlugin $auth */
        global $auth;

        $id = $this->resolvePageId($id);
        if ($user === null) {
            return auth_quickaclcheck($id);
        } else {
            if ($groups === null) {
                $userinfo = $auth->getUserData($user);
                if ($userinfo === false) {
                    $groups = [];
                } else {
                    $groups = $userinfo['grps'];
                }
            }
            return auth_aclcheck($id, $user, $groups);
        }
    }

    /**
     * Lists all links contained in a wiki page
     *
     * @param string $id page id
     * @return array
     * @throws AccessDeniedException  no read access for page
     * @author Michael Klier <chi@chimeric.de>
     *
     */
    public function listLinks($id)
    {
        $id = $this->resolvePageId($id);
        if (auth_quickaclcheck($id) < AUTH_READ) {
            throw new AccessDeniedException('You are not allowed to read this page', 111);
        }
        $links = [];

        // resolve page instructions
        $ins = p_cached_instructions(wikiFN($id));

        // instantiate new Renderer - needed for interwiki links
        $Renderer = new Doku_Renderer_xhtml();
        $Renderer->interwiki = getInterwiki();

        // parse parse instructions
        foreach ($ins as $in) {
            $link = [];
            switch ($in[0]) {
                case 'internallink':
                    $link['type'] = 'local';
                    $link['page'] = $in[1][0];
                    $link['href'] = wl($in[1][0]);
                    $links[] = $link;
                    break;
                case 'externallink':
                    $link['type'] = 'extern';
                    $link['page'] = $in[1][0];
                    $link['href'] = $in[1][0];
                    $links[] = $link;
                    break;
                case 'interwikilink':
                    $url = $Renderer->_resolveInterWiki($in[1][2], $in[1][3]);
                    $link['type'] = 'extern';
                    $link['page'] = $url;
                    $link['href'] = $url;
                    $links[] = $link;
                    break;
            }
        }

        return ($links);
    }

    /**
     * Returns a list of recent changes since give timestamp
     *
     * @param int $timestamp unix timestamp
     * @return array
     * @throws RemoteException no valid timestamp
     * @author Michael Klier <chi@chimeric.de>
     *
     * @author Michael Hamann <michael@content-space.de>
     */
    public function getRecentChanges($timestamp)
    {
        if (strlen($timestamp) != 10) {
            throw new RemoteException('The provided value is not a valid timestamp', 311);
        }

        $recents = getRecentsSince($timestamp);

        $changes = [];

        foreach ($recents as $recent) {
            $change = [];
            $change['name'] = $recent['id'];
            $change['lastModified'] = $this->api->toDate($recent['date']);
            $change['author'] = $recent['user'];
            $change['version'] = $recent['date'];
            $change['perms'] = $recent['perms'];
            $change['size'] = @filesize(wikiFN($recent['id']));
            $changes[] = $change;
        }

        if ($changes !== []) {
            return $changes;
        } else {
            // in case we still have nothing at this point
            throw new RemoteException('There are no changes in the specified timeframe', 321);
        }
    }

    /**
     * Returns a list of recent media changes since give timestamp
     *
     * @param int $timestamp unix timestamp
     * @return array
     * @throws RemoteException no valid timestamp
     * @author Michael Klier <chi@chimeric.de>
     *
     * @author Michael Hamann <michael@content-space.de>
     */
    public function getRecentMediaChanges($timestamp)
    {
        if (strlen($timestamp) != 10)
            throw new RemoteException('The provided value is not a valid timestamp', 311);

        $recents = getRecentsSince($timestamp, null, '', RECENTS_MEDIA_CHANGES);

        $changes = [];

        foreach ($recents as $recent) {
            $change = [];
            $change['name'] = $recent['id'];
            $change['lastModified'] = $this->api->toDate($recent['date']);
            $change['author'] = $recent['user'];
            $change['version'] = $recent['date'];
            $change['perms'] = $recent['perms'];
            $change['size'] = @filesize(mediaFN($recent['id']));
            $changes[] = $change;
        }

        if ($changes !== []) {
            return $changes;
        } else {
            // in case we still have nothing at this point
            throw new RemoteException('There are no changes in the specified timeframe', 321);
        }
    }

    /**
     * Returns a list of available revisions of a given wiki page
     * Number of returned pages is set by $conf['recent']
     * However not accessible pages are skipped, so less than $conf['recent'] could be returned
     *
     * @param string $id page id
     * @param int $first skip the first n changelog lines
     *                      0 = from current(if exists)
     *                      1 = from 1st old rev
     *                      2 = from 2nd old rev, etc
     * @return array
     * @throws AccessDeniedException no read access for page
     * @throws RemoteException empty id
     * @author Michael Klier <chi@chimeric.de>
     *
     */
    public function pageVersions($id, $first = 0)
    {
        $id = $this->resolvePageId($id);
        if (auth_quickaclcheck($id) < AUTH_READ) {
            throw new AccessDeniedException('You are not allowed to read this page', 111);
        }
        global $conf;

        $versions = [];

        if (empty($id)) {
            throw new RemoteException('Empty page ID', 131);
        }

        $first = (int)$first;
        $first_rev = $first - 1;
        $first_rev = $first_rev < 0 ? 0 : $first_rev;

        $pagelog = new PageChangeLog($id);
        $revisions = $pagelog->getRevisions($first_rev, $conf['recent']);

        if ($first == 0) {
            array_unshift($revisions, '');  // include current revision
            if (count($revisions) > $conf['recent']) {
                array_pop($revisions);          // remove extra log entry
            }
        }

        if (!empty($revisions)) {
            foreach ($revisions as $rev) {
                $file = wikiFN($id, $rev);
                $time = @filemtime($file);
                // we check if the page actually exists, if this is not the
                // case this can lead to less pages being returned than
                // specified via $conf['recent']
                if ($time) {
                    $pagelog->setChunkSize(1024);
                    $info = $pagelog->getRevisionInfo($rev ?: $time);
                    if (!empty($info)) {
                        $data = [];
                        $data['user'] = $info['user'];
                        $data['ip'] = $info['ip'];
                        $data['type'] = $info['type'];
                        $data['sum'] = $info['sum'];
                        $data['modified'] = $this->api->toDate($info['date']);
                        $data['version'] = $info['date'];
                        $versions[] = $data;
                    }
                }
            }
            return $versions;
        } else {
            return [];
        }
    }

    /**
     * The version of Wiki RPC API supported
     */
    public function wikiRpcVersion()
    {
        return 2;
    }

    /**
     * Locks or unlocks a given batch of pages
     *
     * Give an associative array with two keys: lock and unlock. Both should contain a
     * list of pages to lock or unlock
     *
     * Returns an associative array with the keys locked, lockfail, unlocked and
     * unlockfail, each containing lists of pages.
     *
     * @param array[] $set list pages with array('lock' => array, 'unlock' => array)
     * @return array
     */
    public function setLocks($set)
    {
        $locked = [];
        $lockfail = [];
        $unlocked = [];
        $unlockfail = [];

        foreach ($set['lock'] as $id) {
            $id = $this->resolvePageId($id);
            if (auth_quickaclcheck($id) < AUTH_EDIT || checklock($id)) {
                $lockfail[] = $id;
            } else {
                lock($id);
                $locked[] = $id;
            }
        }

        foreach ($set['unlock'] as $id) {
            $id = $this->resolvePageId($id);
            if (auth_quickaclcheck($id) < AUTH_EDIT || !unlock($id)) {
                $unlockfail[] = $id;
            } else {
                $unlocked[] = $id;
            }
        }

        return [
            'locked' => $locked,
            'lockfail' => $lockfail,
            'unlocked' => $unlocked,
            'unlockfail' => $unlockfail
        ];
    }

    /**
     * Return API version
     *
     * @return int
     */
    public function getAPIVersion()
    {
        return self::API_VERSION;
    }

    /**
     * Login
     *
     * @param string $user
     * @param string $pass
     * @return int
     */
    public function login($user, $pass)
    {
        global $conf;
        /** @var AuthPlugin $auth */
        global $auth;

        if (!$conf['useacl']) return 0;
        if (!$auth instanceof AuthPlugin) return 0;

        @session_start(); // reopen session for login
        $ok = null;
        if ($auth->canDo('external')) {
            $ok = $auth->trustExternal($user, $pass, false);
        }
        if ($ok === null) {
            $evdata = [
                'user' => $user,
                'password' => $pass,
                'sticky' => false,
                'silent' => true
            ];
            $ok = Event::createAndTrigger('AUTH_LOGIN_CHECK', $evdata, 'auth_login_wrapper');
        }
        session_write_close(); // we're done with the session

        return $ok;
    }

    /**
     * Log off
     *
     * @return int
     */
    public function logoff()
    {
        global $conf;
        global $auth;
        if (!$conf['useacl']) return 0;
        if (!$auth instanceof AuthPlugin) return 0;

        auth_logoff();

        return 1;
    }

    /**
     * Resolve page id
     *
     * @param string $id page id
     * @return string
     */
    private function resolvePageId($id)
    {
        $id = cleanID($id);
        if (empty($id)) {
            global $conf;
            $id = cleanID($conf['start']);
        }
        return $id;
    }
}

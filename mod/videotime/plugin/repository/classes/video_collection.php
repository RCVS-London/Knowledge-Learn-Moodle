<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Video collection definition
 *
 * @package     videotimeplugin_repository
 * @copyright   2019 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace videotimeplugin_repository;

/**
 * Video collection definition
 *
 * @package     videotimeplugin_repository
 * @copyright   2019 bdecent gmbh <https://bdecent.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class video_collection implements \JsonSerializable {
    /**
     * @var video_interface[]
     */
    private $videos = [];

    /**
     * @var string
     */
    private $query;

    /**
     * @var array
     */
    private $albumids = [];

    /**
     * @var array
     */
    private $tagids = [];

    /**
     * @var int
     */
    private $limitfrom = 0;

    /**
     * @var int
     */
    private $limitnum = 0;

    /**
     * @var int Total videos available based on filters.
     */
    private $total;

    /**
     * @var string Field to sort
     */
    private $sort;

    /**
     * @var string Sort direction ASC or DESC
     */
    private $sortdirection;

    /**
     * Constructor
     *
     * @param string $query
     * @param string $albumids
     * @param array $tagids
     * @param int $limitfrom
     * @param int $limitnum
     * @param string $sort
     * @param string $sortdirection
     */
    public function __construct($query = '', $albumids = [], $tagids = [], $limitfrom = 0, $limitnum = 0,
                                string $sort = 'v.id', string $sortdirection = 'ASC') {
        $this->query = $query;
        $this->albumids = $albumids;
        $this->tagids = $tagids;
        $this->limitfrom = $limitfrom;
        $this->limitnum = $limitnum;
        $this->sort = $sort;
        $this->sortdirection = $sortdirection;

        $this->query();
    }

    /**
     * Populate this collection with all valid videos.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function query() {
        global $DB;

        // Only query videos that have been fully processed.
        list($statesql, $stateparams) = $DB->get_in_or_equal([video_interface::STATE_PROCESSED,
            video_interface::STATE_REPROCESS], SQL_PARAMS_NAMED, 'state');

        $sql = 'SELECT DISTINCT v.* FROM {videotime_vimeo_video} v
                LEFT JOIN {videotime_vimeo_video_album} va ON va.video_id = v.id
                LEFT JOIN {videotime_vimeo_album} a ON a.id = va.album_id
                LEFT JOIN {videotime_vimeo_video_tag} vt ON vt.video_id = v.id
                LEFT JOIN {videotime_vimeo_tag} t ON t.id = vt.tag_id
                WHERE v.state ' . $statesql;
        $params = $stateparams;

        if ($this->albumids && count($this->albumids) > 0) {
            list($albumsql, $albumparams) = $DB->get_in_or_equal($this->albumids, SQL_PARAMS_NAMED, 'album');
            $sql .= ' AND a.id ' . $albumsql;
            $params = array_merge($params, $albumparams);
        }

        if ($this->tagids && count($this->tagids) > 0) {
            list($tagsql, $tagparams) = $DB->get_in_or_equal($this->tagids, SQL_PARAMS_NAMED, 'tag');
            $sql .= ' AND t.id ' . $tagsql;
            $params = array_merge($params, $tagparams);
        }

        if (!empty($this->query)) {
            $query = '%' . $this->query . '%';
            $sql .= ' AND (' . $DB->sql_like('v.name', ':v_name', false) .
                ' OR ' . $DB->sql_like('v.description', ':v_description', false) .
                ' OR ' . $DB->sql_like('a.name', ':a_name', false) .
                ' OR ' . $DB->sql_like('t.name', ':t_name', false) . ')';
            $params['v_name'] = $query;
            $params['v_description'] = $query;
            $params['t_name'] = $query;
            $params['a_name'] = $query;
        }

        $sql .= ' ORDER BY ' . $this->sort . ' ' . $this->sortdirection;

        $records = $DB->get_records_sql($sql, $params, $this->limitfrom, $this->limitnum);
        $this->total = count($DB->get_records_sql($sql, $params));

        foreach ($records as $record) {
            $this->videos[$record->id] = video::create($record);
        }
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource.
     *
     * @since 5.4.0
     */
    public function jsonSerialize() {
        return [
            'data' => array_values($this->videos),
            'total' => $this->total
        ];
    }

    /**
     * Get description of data returned with web services.
     *
     * @return \external_description
     */
    public static function get_external_definition() {
        return new \external_single_structure([
            'data' => new \external_multiple_structure(video::get_external_definition()),
            'total' => new \external_value(PARAM_INT)
        ]);
    }
}

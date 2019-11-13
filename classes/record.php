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
 *  Enhanced Sharing Cart
 *
 *  @package    block_enhanced_sharing_cart
 *  @copyright  2017 (C) VERSION2, INC.
 *  @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enhanced_sharing_cart;

require_once __DIR__.'/exception.php';

/**
 *  Enhanced Sharing Cart record manager
 */
class record
{
	const TABLE = 'block_enh_cart'; //table 1
	
	const WEIGHT_BOTTOM = 9999;
	
	public $id       = null;
	public $userid   = null;
	public $modname  = null;
	public $modicon  = '';
	public $modtext  = null;
	public $ctime    = null;
	public $filename = null;
	public $tree     = '';
	public $weight   = 0;
	public $course = 0;
	public $coursefullname = '';
	
	/**
	 *  Constructor
	 *  
	 *  @param mixed $record = empty
	 */
	public function __construct($record = array())
	{
		foreach ((array)$record as $field => $value)
			$this->{$field} = $value;
		
		// default values
		$this->userid or $this->userid = $GLOBALS['USER']->id;
		$this->ctime or $this->ctime = time();
	}
	
	/**
	 *  Create record instance from record ID
	 *  
	 *  @param int $id
	 *  @return record
	 *  @throws exception
	 */
	public static function from_id($id)
	{
		$record = $GLOBALS['DB']->get_record(self::TABLE, array('id' => $id));
		if (!$record)
			throw new exception('recordnotfound');
		return new self($record);
	}
	
	/**
	 *  Insert record
	 *
     *  @return int
	 *  @throws exception
	 */
	public function insert()
	{
		if (!$this->weight)
			$this->weight = self::WEIGHT_BOTTOM;
		$this->id = $GLOBALS['DB']->insert_record(self::TABLE, $this);
		if (!$this->id)
			throw new exception('unexpectederror');
		self::renumber($this->userid);

		return $this->id;
	}
	
	/**
	 *  Update record
	 *  
	 *  @throws exception
	 */
	public function update()
	{
		if (!$GLOBALS['DB']->update_record(self::TABLE, $this))
			throw new exception('unexpectederror');
		self::renumber($this->userid);
	}
	
	/**
	 *  Delete record
	 *  
	 *  @throws exception
	 */
	public function delete()
	{
		$GLOBALS['DB']->delete_records(self::TABLE, array('id' => $this->id));
		self::renumber($this->userid);
	}
	
	/**
	 *  Renumber all items sequentially
	 *  
	 * @global \moodle_database $DB
	 * @global \stdClass $USER
	 * @param int $userid = $USER->id
	 * @throws exception
	 */
	public static function renumber($userid = null)
	{
		global $DB, $USER;
		if ($items = $DB->get_records(self::TABLE, array('userid' => $userid ?: $USER->id))) {
			$tree = array();
			foreach ($items as $it) {
				if (!isset($tree[$it->tree]))
					$tree[$it->tree] = array();
				$tree[$it->tree][] = $it;
			}
			foreach ($tree as $items) {
				usort($items, function ($lhs, $rhs)
				{
					// keep their order if already weighted
					if ($lhs->weight < $rhs->weight) return -1;
					if ($lhs->weight > $rhs->weight) return +1;
					// order by modtext otherwise
					return strnatcasecmp($lhs->modtext, $rhs->modtext);
				});
				foreach ($items as $i => $it) {
					$DB->set_field(self::TABLE, 'weight', 1 + $i, array('id' => $it->id));
				}
			}
		}
	}
}

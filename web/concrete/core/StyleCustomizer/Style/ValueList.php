<?php
namespace Concrete\Core\StyleCustomizer\Style;
use Less_Parser;
use Database;
class ValueList {

    protected $values = array();
    protected $scvlID;

    public function getValues() {
        return $this->values;
    }

    public function getValueListID()
    {
        return $this->scvlID;
    }

    public static function loadFromLessFile($file, $urlroot = false) {
        $l = new Less_Parser();
        $parser = $l->parseFile($file, $urlroot, true);
        $vl = new static();
        $rules = $parser->rules;

        foreach(array('ColorStyle', 'TypeStyle', 'ImageStyle', 'SizeStyle') as $type) {
            $o = '\\Concrete\\Core\\StyleCustomizer\\Style\\' . $type;
            $values = call_user_func_array(array($o, 'getValuesFromVariables'), array($rules));
            $vl->addValues($values);
        }
        return $vl;
    }

    public static function getByID($scvlID)
    {
        $db = Database::get();
        $scvlID = $db->GetOne('select scvlID from StyleCustomizerValueLists where scvlID = ?', array($scvlID));
        if ($scvlID) {
            $o = new static();
            $o->scvlID = $scvlID;
            $rows = $db->fetchAll('select * from StyleCustomizerValues where scvlID = ?', array($scvlID));
            foreach($rows as $row) {
                $o->addValue(unserialize($row['value']));
            }
        }
        return $o;
    }

    public function save()
    {
        $db = Database::get();
        if (!isset($this->scvlID)) {
            $db->insert('StyleCustomizerValueLists', array());
            $this->scvlID = $db->LastInsertId();
        } else {
            $db->delete('StyleCustomizerValues', array('scvlID' => $this->scvlID));
        }

        foreach($this->values as $value) {
            $db->insert('StyleCustomizerValues', array('value' => serialize($value), 'scvlID' => $this->scvlID));
        }
    }

    public function addValue(\Concrete\Core\StyleCustomizer\Style\Value\Value $value)
    {
        $this->values[] = $value;
    }

    public function addValues($values) {
        foreach($values as $value) {
            $this->addValue($value);
        }
    }

}
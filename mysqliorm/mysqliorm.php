<?php
//version 0.21
namespace Indigo7\Db;
abstract class Mysqliorm {
    protected static
        $conn;


    public $id;
    public $db;
    public $mysqli_error;
    public $mysqli_errno;
    public function __construct() {
        $this->db = self::getDB ();
        foreach(static::$db_fields as $key => $field_type) {
            if($field_type == "s") $this->$key='';
            if($field_type == "i") $this->$key=0;
            if($field_type == "d") $this->$key=0;
          }

    }
    public static function setDB (\mysqli $conn)
    {
        self::$conn = $conn;      
    }
    public static function getDB ()
    {
        return self::$conn;
    }
    public function handle_error($stmt = "") {
      //global $db;
      if(!$stmt) {
          $this->mysqli_errno = $db->errno;
          $this->mysqli_error = $db->error;
      } else {
          $this->mysqli_errno = $stmt->errno;
          $this->mysqli_error = $stmt->error;
      }

      return false;
    }
    //static::find(["where" => "removed != 1"])
    public function find($array = []) {
        $where = "";
        $order = "";
        $order_by = "";
        foreach($array as $key => $value) {
          if($key == "where") {
            $where = $value;
          }  
          if($key == "order_by") {
            $order_by = $value;
          }
        }
        if($where) { $where = ' where '.$where; }
        if($order_by) { $order = ' order by '.$order_by; }
        return static::ListbySQL("SELECT * FROM ".static::$table_name.$where.$order);      
    }
    public static function query_affected($query) {
        $db = self::getDB ();
        $db->query($query);
        if($db->affected_rows<1) { return false; } else {
            return true;
        }
    }
    public static function loadbyID($id) {
        $db = self::getDB ();

        if ($result = $db->query("SELECT * FROM ".static::$table_name." WHERE id = {$id}")) {


           // $obj = $result->fetch_object(get_called_class());

           $row = $result->fetch_array(MYSQLI_ASSOC);
              $obj = new static;
          foreach (static::$db_fields as $value_name  => $value_type) {


             $obj->{$value_name} = $row[$value_name];

          }
           $obj->id = $row["id"];
            $result->free();
          if($row["id"]) { return $obj;  } else {
                return false;
          }
        }
        return false;
    }
    public static function ListbySQL($sql)  {
        $db = self::getDB ();
        $objects = array();

        if (!$result = $db->query($sql)) {
          return false;
        }

        while ($row = $result->fetch_assoc()) {
          $obj = new static;
          foreach (static::$db_fields as $value_name  => $value_type) {
              $obj->{$value_name} = $row[$value_name];
          }
          $obj->id = $row["id"];
          $objects[] = $obj;
        }
        $result->free();
        return $objects;

    }
    public static function loadbyField( $field_name, $value) {
        $db = self::getDB ();

        $stmt = $db->prepare("SELECT * FROM `".static::$table_name."` WHERE `".$field_name."` = ?");


         $obj = new static;

        $type = static::$db_fields[$field_name];
        $stmt->bind_param($type, $value);

        /* execute query */
        $stmt->execute();



       // $obj = $stmt->get_result()->fetch_object(get_called_class());
          $result = $stmt->get_result();
         $row = $result->fetch_array(MYSQLI_ASSOC);
          foreach (static::$db_fields as $value_name  => $value_type) {


             $obj->{$value_name} = $row[$value_name];

          }

          $obj->id = $row["id"];



        $stmt->close();

       if($row["id"]) { return $obj;  } else {
       return false;
       }

    }


   public static function bind_param_dynamic($stmt, $params)  {
     if(!$stmt) {
       echo "I die";;
        die();

     }
    if ($params != null)
    {
        // Generate the Type String (eg: 'issisd')
        $types = '';
        foreach ($params as $value_name => $value) {
            $types.= static::$db_fields[$value_name];
        }

        // Add the Type String as the first Parameter
        $bind_names[] = $types;

        // Loop thru the given Parameters
        foreach ($params as $value_name => $value) {
            // Create a variable Name
            $bind_name = $value_name;
            // Add the Parameter to the variable Variable
            $$bind_name = $value;
            // Associate the Variable as an Element in the Array
            $bind_names[] = &$$bind_name;
        }

        // Call the Function bind_param with dynamic Parameters
        call_user_func_array(array($stmt,'bind_param'), $bind_names);
    }   else { return false; }
    return $stmt;
    }

    public static function loadbyFields( $params) {
        $db = self::getDB ();

        $obj = new static;
        foreach($params as $name => $param) {
            $where_blocks[]= " `{$name}` = ? ";
        }
        $where_blocks_string = implode(" and ", $where_blocks);

        $stmt =$db->prepare("SELECT * FROM `".static::$table_name."` WHERE ".$where_blocks_string);


        $stmt = static::bind_param_dynamic($stmt, $params);

        /* execute query */
        $stmt->execute();
        //  print_r($params);


       // $obj = $stmt->get_result()->fetch_object(get_called_class());
          $result = $stmt->get_result();
         $row = $result->fetch_array(MYSQLI_ASSOC);

          foreach (static::$db_fields as $value_name  => $value_type) {


             $obj->{$value_name} = $row[$value_name];

          }

          $obj->id = $row["id"];



        $stmt->close();

       if($row["id"]) { return $obj;  } else {
       return false;
       }

    }



    public function insert($array_values = []) {
      if(!$array_values) {
        foreach (static::$db_fields as $value_name  => $value_type) {
            $value = $this->{$value_name};
            $array_values[$value_name] = $value;
        }
      }

      foreach ($array_values as $value_name => $value) {
          $value_type = static::$db_fields[$value_name];
          if(!$value && $value_type=="s") { $array_values[$value_name] = ""; }
          if(!$value && $value_type=="i") { $array_values[$value_name] = 0; }
          if(!$value && $value_type=="d") { $array_values[$value_name] = 0; }
     }

    foreach($array_values as $name => $param) {
        $names_blocks[] = "`{$name}`";
        $values_blocks[] = "?";
    }

    $names_blocks_string = implode(",",  $names_blocks);
    $values_blocks_string = implode(",", $values_blocks);

    $stmt = $this->db->prepare("INSERT INTO `".static::$table_name."` ($names_blocks_string) values($values_blocks_string);");

    if(!$stmt) {
      return $this->handle_error();
    }





    $stmt = static::bind_param_dynamic($stmt, $array_values);
    if(!$stmt)  {
      return $this->handle_error();
    }

    if(!$stmt->execute()) {
        return $this->handle_error($stmt);
    }
    $this->id = $stmt->insert_id;

    $stmt->close();
    if($this->id) {
           foreach($array_values as $name => $param) {
              $this->{$name} = $param;
          }
          return $this->id;
        } else {
            return false;
        }

    }

    public function update($array_values = []) {
        if(!($array_values)) {

            foreach (static::$db_fields as $value_name  => $value_type) {
                $value = $this->{$value_name};

                $stmt = $this->db->prepare("UPDATE ".static::$table_name." SET ".$value_name." = ? where id = ?;");
                $stmt->bind_param($value_type."i", $value, $this->id);
                if(!$stmt->execute()) {
                    return $this->handle_error($stmt);
                }
                $stmt->close();

            }

        } else {
            foreach ($array_values as $value_name  => $value) {
                $value_type = static::$db_fields[$value_name];
                $stmt = $this->db->prepare("UPDATE `".static::$table_name."` SET `".$value_name."` = ? where id = ?;");

                if(!$stmt) {
                    return $this->handle_error();
                }

                $stmt->bind_param($value_type."i", $value, $this->id);
                $stmt->execute();
                $stmt->close();
                $this->{$value_name} = $value;
            }
        }
        return true;
    }
      public function remove() {

             $stmt = $this->db->prepare("DELETE FROM ".static::$table_name." where id = ?;");
             $stmt->bind_param("i", $this->id);
             $stmt->execute();
             $stmt->close();

             foreach (static::$db_fields as $value_name  => $value_type) {
               $this->{$value_name} = "";
             }

        return true;
   }
   static function remove_all($array_values) {
      $db = self::getDB ();


         foreach ($array_values as $value_name  => $value) {
         $value_type = static::$db_fields[$value_name];
        $stmt = $db->prepare("DELETE FROM ".static::$table_name." where $value_name = ?;");
             $stmt->bind_param($value_type, $value);
             $stmt->execute();
             $stmt->close();

        }
        return true;
   }
}

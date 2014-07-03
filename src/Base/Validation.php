<?php
/**
 * Validation
 *
 * Validates elements in the given array of data. Most often used to validate
 * user input from form submissions.
 *
 */
namespace Base;

class Validation
{
  // Current field
  public $field;
  public $label;

  // Data to validate
  public $data;

  // Array of errors
  public $errors = array();
  public $labels = array();

  // The text to put before an error
  public $error_prefix = '<li>';
  public $error_section_prefix = '<div class="alert alert-danger"><ul>';

  // The text to put after an error
  public $error_suffix = '</li>';
  public $error_section_suffix = '</ul></div>';
  public $html5Validation = array();
  public $modelName;

  public $children = array();
  public $parent;

  public $defaultMessages;

  /**
   * Create the validation object using this data
   *
   * @param array $data to validate
   */
  public function __construct($data = NULL, array $defaultMessages = NULL, $modelName = null)
  {
    $this->data = $data;
    if(!$defaultMessages) {
      $defaultMessages = config('default_validation_messages');
    }
    $this->defaultMessages = (array) $defaultMessages;
    $this->modelName = $modelName;
  }

  public function chain($model, $data = null) {
    $parent = _or($this->parent, $this);
    $child = new Validation();
    $child->parent = $parent;
    $child->forModel($model, $data);
    $parent->children[$child->modelName] = $child;

    return $child;
  }

  public function forModel($model, $data = null) {
    if(is_string($model)) {
      $this->modelName = $model;
    } else {
      $this->modelName = snakeCase(array_pop(explode('\\', get_class($model))));
      $data = (array)$model;
    }
    if(!$data) {
      $data = request($this->modelName);
    }
    $this->data = $data;
    return $this;
  }


  /**
   * Add a new field to the validation object
   *
   * @param string $field name
   */
  public function field($field, $label = NULL)
  {
    $this->field = $field;
    if($label) {
      $this->label($label);
    } else {
      $this->label(labelify($field));
    }
    return $this;
  }

  public function label($label) {
    $this->label = $label;
    if(!$this->field) {
      $this->field(snakeCase($label));
    }
    $this->labels[$this->field] = $label;
    return $this;
  }

  /**
   * Return the value of the given field
   *
   * @param string $field name to use instead of current field
   * @return mixed
   */
  public function value($field = NULL)
  {
    if( ! $field)
    {
      $field = $this->field;
    }

    if(isset($this->data[$field]))
    {
      return $this->data[$field];
    }
  }


  /**
   * Return success if validation passes!
   *
   * @return boolean
   */
  public function validates()
  {
    if($this->children) {
      foreach ($this->children as $name => $child) {
        if( !$child->validates()) {
          return false;
        }
      }
    }
    return ! $this->errors;
  }


  /**
   * Fetch validation error for the given field
   *
   * @param string $field name to use instead of current field
   * @param boolean $wrap error with suffix/prefix
   * @return string
   */
  public function error($field = NULL, $wrap = FALSE)
  {
    if( ! $field)
    {
      $field = $this->field;
    }

    if(isset($this->errors[$field]))
    {
      if($wrap)
      {
        return $this->error_prefix . $this->errors[$field] . $this->error_suffix;
      }

      return $this->errors[$field];
    }
  }

  public function processError($str, $a = '', $b ='') {
    $str = str_replace(':label', $this->label, $str);
    $str = str_replace(':name', $this->label, $str);
    $str = str_replace(':field', $this->field, $str);
    if(!is_array($a)) $str = str_replace(':1', $a, $str);
    if(!is_array($b)) $str = str_replace(':2', $b, $str);
    return $str;
  }


  /**
   * Return all validation errors as an array
   *
   * @return array
   */
  public function errors()
  {
    if($this->children) {
      $out = array() + $this->errors();
      foreach ($this->children as $child) {
        $out += $child->errors;
      }
      return $out;
    }
    return $this->errors;
  }


  /**
   * Return all validation errors wrapped in HTML suffix/prefix
   *
   * @return string
   */
  public function __toString()
  {
    $output = '';
    foreach($this->errors as $field => $error)
    {
      $output .= $this->error_prefix . $this->errors[$field] . $this->error_suffix . "\n";
    }
    foreach ($this->children as $child) {
      $output .= (string)$child;
    }
    if($output) {
      $output = $this->error_section_prefix . $output . $this->error_section_suffix;
    }
    return $output;
  }


  /**
   * Middle-man to all rule functions to set the correct error on failure.
   *
   * @param string $rule
   * @param array $args
   * @return this
   */
  public function __call($rule, $args)
  {
    if(isset($this->errors[$this->field]) OR empty($this->data[$this->field])) return $this;

    // Add method suffix
    $method = $rule . '_rule';

    // Defaults for $error, $params
    $args = $args + array(NULL, NULL, NULL);
    $msg = null;
    if(is_null($args[0]) || is_string($args[0])) {
      $msg = array_shift($args);
    }
    $extra = $args[0];
    $extrab = $args[1];


    // If the validation fails
    if( ! $this->$method($this->data[$this->field], $extra, $extrab))
    {
      if(!$msg) {
        $msg = $this->defaultMessages[$rule];
      }
      $this->errors[$this->field] = $this->processError($msg, $extra, $extrab);
    }

    return $this;
  }


  /**
   * Value is required and cannot be empty.
   *
   * @param string $error message
   * @param boolean $string set to true if data must be string type
   * @return boolean
   */
  public function required($error = NULL, $string = TRUE)
  {
    if(!$error) {
      $error = $this->defaultMessages['required'];
    }
    $this->html5Validation[$this->field] = array('required' => true);
    if(empty($this->data[$this->field]) OR ($string AND is_array($this->data[$this->field])))
    {
      $this->errors[$this->field] = $this->processError($error);
    }

    return $this;
  }


  /**
   * Verify value is a string.
   *
   * @param mixed $data to validate
   * @return boolean
   */
  protected function string_rule($data)
  {
    return is_string($data);
  }


  /**
   * Verify value is an array.
   *
   * @param mixed $data to validate
   * @return boolean
   */
  protected function array_rule($data)
  {
    return is_array($data);
  }


  /**
   * Verify value is an integer
   *
   * @param string $data to validate
   * @return boolean
   */
  protected function integer_rule($data)
  {
    return is_int($data) OR ctype_digit($data);
  }

  protected function number_rule($data) {
    return is_numeric($data);
  }

  protected function id_rule($data, $model = null)
  {
    if($this->integer_rule($data)) {
      if(!is_null($data) && $data > 0) {
        if($model) {
          // TODO: Test for id presence
        }
        return true;
      }
    }
    return false;
  }


  /**
   * Verifies the given date string is a valid date using the format provided.
   *
   * @param string $data to validate
   * @param string $format of date string
   * @return boolean
   */
  protected function date_rule($data, $format = NULL)
  {
    if($format)
    {
      if($data = DateTime::createFromFormat($data, $format))
      {
        return TRUE;
      }
    }
    elseif($data = strtotime($data))
    {
      return TRUE;
    }
  }


  /**
   * Condition must be true.
   *
   * @param mixed $data to validate
   * @param boolean $condition to test
   * @return boolean
   */
  protected function true_rule($data, $condition)
  {
    return $condition;
  }


  /**
   * Field must have a value matching one of the options
   *
   * @param mixed $data to validate
   * @param array $array of posible values
   * @return boolean
   */
  protected function options_rule($data, $options)
  {
    return in_array($data, $options);
  }


  /**
   * Validate that the given value is a valid IP4/6 address.
   *
   * @param mixed $data to validate
   * @return boolean
   */
  protected function ip_rule($data)
  {
    return (filter_var($data, FILTER_VALIDATE_IP) !== false);
  }


  /**
   * Verify that the value of a field matches another one.
   *
   * @param mixed $data to validate
   * @param string $field name of the other element
   * @return boolean
   */
  protected function matches_rule($data, $field)
  {
    if(isset($this->data[$field]))
    {
      return $data === $this->data[$field];
    }
  }


  /**
   * Check to see if the email entered is valid.
   *
   * @param string $data to validate
   * @return boolean
   */
  protected function email_rule($data)
  {
    return preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $data);
  }


  /**
   * Must only contain word characters (A-Za-z0-9_).
   *
   * @param string $data to validate
   * @return boolean
   */
  protected function word_rule($data)
  {
    return ! preg_match("/\W/", $data);
  }


  /**
   * Plain text that contains no HTML/XML "><" characters.
   *
   * @param string $data to validate
   * @return boolean
   */
  protected function plaintext_rule($data)
  {
    return (mb_strpos($data, '<') === FALSE AND mb_strpos($data, '>') === FALSE);
  }


  /**
   * Minimum length of the string.
   *
   * @param string $data to validate
   * @param int $length of the string
   * @return boolean
   */
  protected function min_rule($data, $length)
  {
    return mb_strlen($data) >= $length;
  }


  /**
   * Maximum length of the string.
   *
   * @param string $data to validate
   * @param int $length of the string
   * @return boolean
   */
  protected function max_rule($data, $length)
  {
    return mb_strlen($data) <= $length;
  }

  /**
   * between length of the string.
   *
   * @param string $data to validate
   * @param int $min of the string
   * @param int $max of the string
   * @return boolean
   */
  protected function between_rule($data, $min, $max)
  {
    $strlen = mb_strlen($data);
    return ($max > $min) AND ($strlen >= $min) AND ($strlen <= $max);
  }

  /**
   * Exact length of the string.
   *
   * @param string $data to validate
   * @param int $length of the string
   * @return boolean
   */
  protected function length_rule($data, $length)
  {
    return mb_strlen($data) === $length;
  }


  protected function boolean_rule($data)
  {
    return $data === '0' || $data === '1';
  }


  /**
   * Tests a string for characters outside of the Base64 alphabet
   * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
   *
   * @param string $data to validate
   * @return boolean
   */
  protected function base64_rule($data)
  {
    return preg_match('/[^a-zA-Z0-9\/\+=]/', $data);
  }

}

// END
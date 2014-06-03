<?php
/**
 * Form
 *
 * Create HTML forms from a validation object using method chaining.
 *
 */
namespace Base;

class Form
{
  // Array of field objects
  protected $fields;

  protected $field;
  protected $validation;
  protected $attributes;
  protected $value;
  protected $label;
  protected $labelAttrs;
  protected $type = 'text';
  protected $options;
  protected $tag;
  protected $tagAttrs;
  protected $ctrlAttrs;
  protected $ctrlTag;
  protected $config;
  protected $prefix;
  protected $suffix;
  protected $ctrlSuffix;
  protected $ctrlPrefix;
  protected $dataListId;
  public $inlineValidation = TRUE;
  public $nested = FALSE;
  public $children = array();

  /**
   * Create an HTML form containing the given fields
   *
   * @param object $validation object
   * @param string $field name
   */
  public function __construct($validation = NULL, $field = NULL)
  {
    $this->validation = $validation;
    $this->field = $field;

    if($field)
    {
      $this->attributes = array();
    }
  }

  public function forModel($name) {
    if($this->validation->children[$name]) {
      $child = new Form($this->validation->children[$name]);
      return $this->children[] = $child;
    }
    throw new \Exception($name . ' not known');
  }

  public function setConfig($config) {
    $this->ctrlTag = @$config['ctrlTag'];
    $this->ctrlAttrs = @$config['ctrlAttrs'];
    $this->tag = @$config['tag'];
    $this->tagAttrs = @$config['tagAttrs'];
    $this->labelAttrs = @$config['labelAttrs'];
    $this->attributes = @$config['attributes'];
    $this->prefix = @$config['prefix'];
    $this->suffix = @$config['suffix'];
    $this->ctrlPrefix = @$config['ctrlPrefix'];
    $this->ctrlSuffix = @$config['ctrlSuffix'];
    $this->config = @$config;
    return $this;
  }

  public function fieldName() {
    if($this->validation->modelName) {
      return sprintf('%s[%s]', $this->validation->modelName, $this->field);
    } else {
      return $this->field;
    }
  }

  public function id() {
    if(!$this->validation->modelName) {
      return $this->field;
    } else {
      return sprintf('%s_%s', $this->validation->modelName, $this->field);
    }
  }


  /**
   * Add an array of attributes to the form element
   *
   * @param array $attributes to add
   */
  public function attributes(array $attributes)
  {
    foreach($attributes as $key => $value)
    {
      $this->attributes[$key] = $value;
    }
    return $this;
  }

  public function addAttribute($key, $value) {
    if(!$this->attributes) $this->attributes = array();
    $this->attributes[$key] = $value;
    return $this;
  }

  public function placeholder($str) {
    return $this->addAttribute('placeholder', $str);
  }

  public function help($str) {
    return $this->placeholder($str);
  }


  /**
   * Load a new instance of this object for the given field
   */
  public function __get($field)
  {
    return $this->getField($field);
  }

  public function getField($field) {
    if(empty($this->fields[$field]))
    {
      if($this->field) $this->nested = true;
      $this->fields[$field] = new $this($this->validation, $field, $this->attributes);
      $this->fields[$field]->inlineValidation = $this->inlineValidation;
      if(!$this->nested) $this->fields[$field]->setConfig($this->config);
    }
    return $this->fields[$field];

  }


  /**
   * Set the field value
   *
   * @param mixed $value
   */
  public function value($value)
  {
    $this->value = $value;
    return $this;
  }

  public function bonusHtml($prefix = null, $suffix = null) {
    if($prefix) $this->$prefix = $prefix;
    if($suffix) $this->$suffix = $suffix;
  }

  public function bonusCtrlHtml($ctrlPrefix = null, $ctrlSuffix = null) {
    if($ctrlPrefix) $this->$ctrlPrefix = $ctrlPrefix;
    if($ctrlSuffix) $this->$ctrlSuffix = $ctrlSuffix;
  }

  /**
   * Add a form label before the given element
   *
   * @param string $label text
   */
  public function label($label = NULL, $attrs = array())
  {
    if(!$label && array_key_exists($this->field, $this->validation->labels)) {
      $label = $this->validation->labels[$this->field];
    }
    $this->label = $label;
    if($attrs) $this->labelAttrs = $attrs;
    return $this;
  }


  /**
   * Set the form element to display as a selectbox
   *
   * @param array $options of select box
   */
  public function select(array $options)
  {
    $this->type = 'select';
    $this->options = $options;
    return $this;
  }

  // For now, a combo box is just a select.
  public function combo(array $options)
  {
    $this->type = 'text';
    $this->dataListId = 'datalist' . count($options);
    $this->attributes['list'] = $this->dataListId;
    $this->options = $options;
    return $this;
  }


  /**
   * Set the form element to display as an input box
   *
   * @param string $type of input (text, password, hidden...)
   */
  public function input($type = 'text')
  {
    $this->type = $type;
    if($type == 'checkbox' && !$this->value) {
      $this->value = 1;
    }
    return $this;
  }

  public function checkbox($value = 1) {
    $this->input('checkbox');
    if(!is_null($value)) {
      $this->value($value);
    }
    $this->setConfig(config('form')->checkBox);
    return $this;
  }

  public function hidden($value = null) {
    $this->input('hidden');
    if($value) {
      $this->value($value);
    }
    return $this;
  }

  public function password() {
    return $this->input('password');
  }

  public function display()
  {
    $this->type = 'display';
    return $this;
  }

  /**
   * Set the form element to display as a textarea
   */
  public function textarea($cols = null, $rows = null)
  {
    if($cols) {
      $this->attributes['cols'] = $cols;
    }
    if($rows) {
      $this->attributes['rows'] = $rows;
    }
    $this->type = 'textarea';
    return $this;
  }


  /**
   * Wrap the given form element in this tag
   *
   * @param string $tag name
   */
  public function wrap($tag = 'div', array $attrs = NULL)
  {
    if(!$attrs) {
      $attrs = array('class'=>'form-control');
    }
    $this->tag = $tag;
    $this->tagAttrs = $attrs;
    return $this;
  }

  public function wrapControl($tag = 'div', array $attrs = NULL)
  {
    $this->ctrlTag = $tag;
    $this->ctrlAttrs = $attrs;
    return $this;
  }


  /**
   * Return the current HTML form as a string
   */
  public function __toString()
  {
    try
    {
      if($this->field)
      {
        return $this->render_field();
      }

      if( ! $this->fields) return '';

      $output = '';

      foreach($this->fields as $field) $output .= $field;
      foreach($this->children as $child) {
        $output .= (string)$child;
      }

      return $output;
    }
    catch(\Exception $e)
    {
      Error::exception($e);
      return '';
    }
  }


  /**
   * Render the given field
   *
   * @return string
   */
  protected function render_field()
  {
    $html = "\n";
    if($this->prefix) $html .= $this->prefix;

    if( ! $this->attributes)
    {
      $this->attributes = array();
    }

    // Configure the attributes
    $attributes = $this->attributes;
    $attributes += array('name' => $this->fieldName(), 'id' => $this->id());
    $isHidden = false;

    // Get the current value
    if($this->value !== NULL)
    {
      $value = $this->value;
    }
    else
    {
      $value = $this->validation->value($this->field);
    }

    // HTML5 Validation
    $html5ValAttrs = @$this->validation->html5Validation[$this->field];
    if($html5ValAttrs) {
      $attributes += $html5ValAttrs;
    }

    if(!$this->labelAttrs) $this->labelAttrs = array();

    if($this->label && $this->type != 'checkbox')
    {
      $this->labelAttrs += array('for'=>$this->fieldName());
      $html .= \Base\HTML::tag('label', $this->label, $this->labelAttrs);
    }
    $ctrl = '';

    if($this->type == 'select')
    {
      $ctrl = \Base\HTML::select($this->fieldName(), $this->options, $value, $attributes);
    }
    elseif($this->type == 'textarea')
    {
      $ctrl = \Base\HTML::tag('textarea', $value, $attributes);
    }
    elseif($this->type == 'display')
    {
      $ctrl = \Base\HTML::tag('p', h($value), $attributes);
    }
    else
    {
      if(!$this->type) $this->type = 'text';
      // Input field
      $attributes = $attributes + array('type' => $this->type, 'value' => $value);

      if($this->type == 'checkbox') {
        $html .= \Base\HTML::tag('input', FALSE, array('type'=>'hidden', 'value'=>'0', 'name'=>$this->fieldName()));
        $val = $this->validation->value($this->field);
        if($val == $this->value) {
          $attributes += array('checked'=>true);
        }
      }

      $ctrl = \Base\HTML::tag('input', FALSE, $attributes);
      if(array_key_exists('type', $attributes) && $attributes['type'] == 'hidden') {
        $isHidden = true;
      }
      if($this->type == 'checkbox' && $this->label) {
        $ctrl = \Base\HTML::tag('label', $ctrl . $this->label, $this->labelAttrs);
      }
    }
    if($this->dataListId) {
      $ctrl .= \Base\HTML::datalist($this->dataListId, $this->options, $this->fieldName(), $value);
    }

    if(!$isHidden && $this->ctrlPrefix) $html .= $this->ctrlPrefix;
    if(!$isHidden && $this->ctrlTag) {
      $html .= \Base\HTML::tag($this->ctrlTag, $ctrl, $this->ctrlAttrs);
    } else {
      $html .= $ctrl;
    }
    if(!$isHidden && $this->ctrlSuffix) $html .= $this->ctrlSuffix;

    if($this->nested) {
      foreach ($this->fields as $nested) {
        $html .= $nested;
      }
    }

    // If there was a validation error
    if($error = $this->validation->error($this->field))
    {
      if(isset($attributes['class']))
      {
        $attributes['class'] .= ' has-error';
      }
      else
      {
        $attributes['class'] = $this->field . ' ' . $this->type . ' has-error';
      }

      if($this->inlineValidation) $html .= "\n<div class=\"help-block\">$error</div>";
    }

    if(!$isHidden && $this->tag)
    {
      if($this->tag == 'td') {
        $html = \Base\HTML::tag('p', $html);
      }
      $html = \Base\HTML::tag($this->tag, $html . "\n", $this->tagAttrs) . "\n";
    }

    if($this->suffix) $html .= $this->suffix;

    return $html;
  }

}

// END
<?php
/**
 * Table
 *
 * Create sortable HTML tables from result sets.
 *
 */
namespace Base;

class Table
{
  // Array of data rows
  public $rows;

  // List of all table columns
  public $columns;

  // Ordering parameters
  public $column;
  public $sort;

  // Existing parameters
  public $params;

  // Table ID, class, etc
  public $attributes;
  public $rowClass;
  public $datumClass;
  public $datumWrap;
  public $headerClass;

  public $columnParam = 'column';
  public $sortParam = 'sort';

  /**
   * Create the table object using these rows
   *
   * @param array $rows to use
   */
  public function __construct($rows, $theme = 'theme.table')
  {
    $this->rows = $rows;

    // Set order defaults
    $this->params = $_GET;
    $this->columnParam = $columnParam;
    $this->sortParam = $sortParam;
    $this->column = get($columnParam);
    $this->sort = get($sortParam, 'asc');
    $this->attributes = array('class' => 'table');
    theme($theme, $this);
  }


  /**
   * Add a new field to the validation object
   *
   * @param string $field name
   */
  public function column($header, $name, $function = NULL)
  {
    $this->columns[$header] = array($name, $function);

    return $this;
  }


  public function render()
  {
    $headerClass = '';
    if($this->headerClass) {
      $headerClass = ' class="' . $this->headerClass . '"';
    }
    $html = "\n\t<thead>\n\t\t<tr" . $headerClass . ">";

    $index = 0;
    foreach($this->columns as $header => $data)
    {
      $html .= "\n\t\t\t<th id='header_";

      // If we allow sorting by this column
      if($data[0])
      {
        $html .= $data[0] . "'";
        // If this column matches the current sort column - go in reverse
        if($this->column === $data[0])
        {
          $sort = $this->sort == 'asc' ? 'desc' : 'asc';
        }
        else
        {
          $sort = $this->sort == 'asc' ? 'asc' : 'desc';
        }

        // Build URL parameters taking existing parameters into account
        $url = site_url(NULL, array($this->columnParam => $data[0], $this->sortParam => $this->sort) + $this->params);

        $html .= '><a href="' . $url . '" class="table_sort_' . $sort . '">' . $header . '</a>';
      }
      else
      {
        $html .= $index . "'>" . $header;
      }
      $index++;

      $html .= "</th>";
    }

    $html .= "\n\t\t</tr>\n\t</thead>\n\t<tbody>";

    $odd = 0;
    $td = 'td' . ($this->datumClass ? ' class="' . $this->datumClass . '"' : '');
    foreach($this->rows as $row)
    {
      $odd = 1 - $odd;

      $html .= "\n\t\t<tr class=\"". ($odd ? 'odd' : 'even') . '">';
      foreach($this->columns as $header => $data)
      {
        if($data[1])
        {
          $datum = $data[1]($row);
        }
        else
        {
          $datum = h($row->$data[0]);
        }
        if($this->datumWrap) {
          $datum = HTML::tag($this->datumWrap, $datum);
        }
        $html .=  "\n\t\t\t<$td>" . $datum . "</td>";
      }
      $html .= "\n\t\t</tr>";
    }

    $html .= "\n\t</tbody>\n";

    return HTML::tag('table', $html, $this->attributes);
  }


  /**
   * alias for render()
   *
   * @return string
   */
  public function __toString()
  {
    try
    {
      return $this->render();
    }
    catch(\Exception $e)
    {
      Error::exception($e);
      return '';
    }
  }
}

// END
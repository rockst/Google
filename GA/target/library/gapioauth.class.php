<?php
/**
 * GAPI - Google Analytics PHP Interface
 * 
 * http://code.google.com/p/gapi-google-analytics-php-interface/
 * 
 * @copyright Stig Manning 2009
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @author Stig Manning <stig@sdm.co.nz>
 * @version 1.3
 * 
 */

class gapi
{
 
  const dev_mode = false;  
  private  $analytics;
  
 
  function __construct(&$anlaytics) {
    $this->analytics = $anlaytics;
  }
 
   /**
   * Case insensitive array_key_exists function, also returns
   * matching key.
   *
   * @param String $key
   * @param Array $search
   * @return String Matching array key
   */
  public static function array_key_exists_nc($key, $search)
  {
  
    if (array_key_exists($key, $search))
    {
      return $key;
    }
    if (!(is_string($key) && is_array($search)))
    {
      return false;
    }
    $key = strtolower($key);
    foreach ($search as $k => $v)
    {
      if (strtolower($k) == $key)
      {
        return $k;
      }
    }
    return false;
  }
  
  /**
   * Request report data from Google Analytics
   *
   * $report_id is the Google report ID for the selected account
   * 
   * $parameters should be in key => value format
   * 
   * @param String $report_id
   * @param Array $dimensions Google Analytics dimensions e.g. array('browser')
   * @param Array $metrics Google Analytics metrics e.g. array('pageviews')
   * @param Array $sort_metric OPTIONAL: Dimension or dimensions to sort by e.g.('-visits')
   * @param String $filter OPTIONAL: Filter logic for filtering results
   * @param String $start_date OPTIONAL: Start of reporting period
   * @param String $end_date OPTIONAL: End of reporting period
   * @param Int $start_index OPTIONAL: Start index of results
   * @param Int $max_results OPTIONAL: Max results returned
   */
  public function requestReportData($profileId, $dimensions, $metrics, $sort_metric=null, $filter=null, $start_date=null, $end_date=null, $start_index=1, $max_results=100,$seg='')
  {
     $this->report_root_parameters = array();
	 $this->results = array(); 
     $parameters=array();
	 $metrics_string = '';
    if($dimensions)
    if(is_array($dimensions))
    {
      $dimensions_string = '';
      foreach($dimensions as $dimesion)
      {
        $dimensions_string .= ',ga:' . $dimesion;
      }
      $parameters['dimensions'] = substr($dimensions_string,1);
    }
    else 
    {
      $parameters['dimensions'] = 'ga:'.$dimensions;
    }

    if(is_array($metrics))
    {
      
      foreach($metrics as $metric)
      {
        $metrics_string .= ',ga:' . $metric;
      }
       $metrics_string = substr($metrics_string,1);
    }
    else 
    {
       $metrics_string = 'ga:'.$metrics;
    }
    
 
    if($sort_metric==null && !empty( $metrics_string))
    {
      $parameters['sort'] =  $metrics_string;
    }
    elseif(is_array($sort_metric))
    {
      $sort_metric_string = '';
      
      foreach($sort_metric as $sort_metric_value)
      {
        //Reverse sort - Thanks Nick Sullivan
        if (substr($sort_metric_value, 0, 1) == "-")
        {
          $sort_metric_string .= ',-ga:' . substr($sort_metric_value, 1); // Descending
        }
        else
        {
          $sort_metric_string .= ',ga:' . $sort_metric_value; // Ascending
        }
      }
      
      $parameters['sort'] = substr($sort_metric_string, 1);
    }
    else 
    {
      if (substr($sort_metric, 0, 1) == "-")
      {
        $parameters['sort'] = '-ga:' . substr($sort_metric, 1);
      }
      else 
      {
        $parameters['sort'] = 'ga:' . $sort_metric;
      }
    }
    
    if($filter!=null)
    {
      $filter = $this->processFilter($filter);
      if($filter!==false)
      {
        $parameters['filters'] = $filter;
      }
    }
    
    if($start_date==null)
    {
      $start_date=date('Y-m-d',strtotime('1 month ago'));
    }
    
   
    
    if($end_date==null)
    {
      $end_date=date('Y-m-d');
    }
    
     
     
    $parameters['max-results'] = $max_results;
    
  
	
	 try {
			$result=$this->analytics->data_ga->get(
			'ga:' . $profileId,
			 $start_date,
			 $end_date,
			 $metrics_string,
			 $parameters);
			 
		    $rows=$result->getRows();
			$this->results = null;
			$results = array();
			
			$report_root_parameters = array(); 
			
			$totals = $result->gettotalsForAllResults();		 
			foreach ($totals as  $metricName => $metricTotal )
			{ 
			  $report_root_parameters[str_replace('ga:','',$metricName)]=$metricTotal; 
			}   
			
			$report_root_parameters['totalResults']=$result->gettotalResults();
			$report_root_parameters['nextLink']=$result->getnextLink();
			$report_root_parameters['selfLink']=$result->getselfLink();
			$report_root_parameters['haveData']=count($rows)>0 ? true:false;
			if(count($rows)>0)
			{
			$metrics=Array();$dimensions = array();
			foreach ($result->getColumnHeaders() as $header) {
			   
			   $metrics[]=str_replace('ga:','',$header->getName());
			   if($header->getColumnType()=='DIMENSION')
			   {
			     $dimensions[]=str_replace('ga:','',$header->getName());
			   }
		    }
			foreach ($rows as $row) {
			  $metric=Array();$dimension=Array();$i=0;
			  foreach ($row as $cell) {
				
					  $metric[$metrics[$i]]=$cell;
					  $i++;
					   
			  }
			  foreach($dimensions as $v)
			  {
			    $dimension[$v]=$metric[$v];
			  }
			 $results[]= new gapiReportEntry ($metric,$dimension);
			}
			}
	    
		 $this->report_root_parameters = $report_root_parameters;
		 
		  $this->results = $results;
		
		return $results;
	  
    }  catch ( Exception $e) {
     
      
	  throw new  Exception( $e->getMessage() );
	  
    } 
	 
    return '';
	
	 
      
     
  }

  /**
   * Process filter string, clean parameters and convert to Google Analytics
   * compatible format
   * 
   * @param String $filter
   * @return String Compatible filter string
   */
  protected function processFilter($filter)
  {
    $valid_operators = '(!~|=~|==|!=|>|<|>=|<=|=@|!@)';
    
    $filter = preg_replace('/\s\s+/',' ',trim($filter)); //Clean duplicate whitespace
    $filter = str_replace(array(',',';'),array('\,','\;'),$filter); //Escape Google Analytics reserved characters
    $filter = preg_replace('/(&&\s*|\|\|\s*|^)([a-z]+)(\s*' . $valid_operators . ')/i','$1ga:$2$3',$filter); //Prefix ga: to metrics and dimensions
    $filter = preg_replace('/[\'\"]/i','',$filter); //Clear invalid quote characters
    $filter = preg_replace(array('/\s*&&\s*/','/\s*\|\|\s*/','/\s*' . $valid_operators . '\s*/'),array(';',',','$1'),$filter); //Clean up operators
    
    if(strlen($filter)>0)
    {
      return  ($filter);
    }
    else 
    {
      return false;
    }
  } 
  /**
   * Get Results
   *
   * @return Array
   */
  public function getResults()
  {
    if(is_array($this->results))
    {
      return $this->results;
    }
    else 
    {
      return;
    }
  }
  
  
  
  /**
   * Call method to find a matching root parameter or 
   * aggregate metric to return
   *
   * @param $name String name of function called
   * @return String
   * @throws Exception if not a valid parameter or aggregate 
   * metric, or not a 'get' function
   */
  public function __call($name,$parameters)
  {
    if(!preg_match('/^get/',$name))
    {
      throw new Exception('No such function "' . $name . '"');
    }
    
    $name = preg_replace('/^get/','',$name);
    
    $parameter_key = gapi::array_key_exists_nc($name,$this->report_root_parameters);
    
    if($parameter_key)
    {
      return $this->report_root_parameters[$parameter_key];
    }
 
    throw new Exception('No valid root parameter or aggregate metric called "' . $name . '"');
  }
  
   
}


/**
 * Class gapiReportEntry
 * 
 * Storage for individual gapi report entries
 *
 */
class gapiReportEntry
{
	  private $metrics = array();
	 private $dimensions = array();
	 public function __construct($metrics,$dimesions)
	 {
		$this->metrics = $metrics;
		$this->dimensions = $dimesions;
	 }
 
   public function __toString()
  {
    if(is_array($this->dimensions))
    {
      return implode(' ',$this->dimensions);
    }
    else 
    {
      return '';
    }
  } 
  
  /**
   * Get an array of the metrics and the matchning
   * values for the current result
   *
   * @return Array
   */
  public function getMetrics()
  {
    return $this->metrics;
  }
  
  /**
   * Call method to find a matching metric or dimension to return
   *
   * @param $name String name of function called
   * @return String
   * @throws Exception if not a valid metric or dimensions, or not a 'get' function
   */
  public function __call($name,$parameters)
  {
    if(!preg_match('/^get/',$name))
    {
      throw new Exception('No such function "' . $name . '"');
    }
    
    $name = preg_replace('/^get/','',$name);
    
    $metric_key = gapi::array_key_exists_nc($name,$this->metrics);
    
    if($metric_key)
    {
      return $this->metrics[$metric_key];
    }
    
   

    throw new Exception('No valid metric or dimesion called "' . $name . '"');
  }
}


<?php
// printTable Advanced - 08/04/22 - DS
// v0.88 - added $colWidth and $TDinnerFieldClasses
// v1.00 - fixed invalid HTML, added 'degrade' mode
// v1.10 - 01/19/23 - simplified input, removed filter button features and added HTMX features.

//experimental performance version

class zPTAs
{
	private static $rows = []; //internal buffer of PTA rows to output.
	
	//fields that get sucked into input
	public static $orderBy = [];
	public static $offset = '';
	
	//settings that get set at init time
	public static $orderByFields = [];
	public static $showFields = [];
	public static $THfieldClasses = [];
	public static $TDfieldClasses = [];
	public static $degrade = false;
	public static $degradeTHwidths = [];
	public static $htmxTargetSelector = '';
	public static $htmxIncludeSelector = '';
	public static $htmxGenerateTRIDField = '';
	public static $name = '';
	public static $extraWrapperClasses = '';
	public static $rowsPerPage = 0;
	
	//buffer vars between classes
	public static $rowsTotal = '';
	public static $injectLinkString = '';
	
	//produce a printTable Advanced line ( TR ) and return it into an array for later printing.
	//$bgClasses can be used to set a row style.
	public static function addRow($rowData, $bgClasses) { self::$rows[] = get_defined_vars(); }

	//printTable Advanced - produces a table with variable sorting.
	//Allows intermediate mutation of rows between the prepare and output statements.

	//Initialize the PTA.
	public static function prepare
	(
		//mandatory fields
		$DBtable,                   //The database table we read from.
		$name = 'PTA',              //Will determine the ID of the table
		$showFields = [],           //Show only specific fields; SQL field => english names relations
		$orderByFields = '',        //(piped) list of database fields that show a sort ^v in the TH in each field.
		
		//optional logic fields
		$defaultOrderBy = [],       //Default SQL order; SQL field => ASC/DESC
		$injectSQLwhere = '',       //Append a fixed SQL string at the end of the search.
		$injectLinkString = '',     //Append a fixed linkString at the end of generated linkString.
		$rowsPerPage = 0,           //If set to anything other than 0, pagination is shown.
		$selectFields = '*',        //The fields to include during SELECT.
		
		//optional visual fields
		$THfieldClasses = [],       //SQL field => classname for given TH field.
		$TDfieldClasses = [],       //Add a custom div to the <td> field => classname.
		$extraWrapperClasses = '',  //overall wrapper class for the table.
		$degrade = false,           //enable auto ellipsing of text; different CSS layout.
		$degradeTHwidths = '',      //SQL field => grid-template-columns parameter for given TH field in degrade mode.
		$htmxTargetSelector = '',   //set a HTMX hx-target; if any, will add appropriate links.
		$htmxIncludeSelector = '',  //set a HTMX hx-include; useful for grabbing form input to submit later.
		$htmxGenerateTRIDField = '' //set a field that will be included to generate a TR ID per row
	)
	{
		//for debugging help --v
		//zl::quipD(get_defined_vars());
		
		//get input from form/url, convert, and set.
		$temp = zfilter::array('zOrd|zOff', 'stringExtended');
		self::$orderBy = zarr::toArray($temp['zOrd']);
		self::$offset = intval($temp['zOff']);
		unset($temp);
		
		//load parameters into the class for later use.
		self::$orderByFields = zarr::toArray($orderByFields);
		self::$showFields = zarr::toArray($showFields);
		self::$THfieldClasses = zarr::toArray($THfieldClasses);
		self::$TDfieldClasses = zarr::toArray($TDfieldClasses);
		self::$degrade = $degrade;
		self::$degradeTHwidths = $degradeTHwidths;
		
		//out() needs this in memory.
		self::$extraWrapperClasses = $extraWrapperClasses;
		self::$rowsPerPage = intval($rowsPerPage);
		self::$name = $name;
		self::$injectLinkString = $injectLinkString;
		self::$htmxTargetSelector = $htmxTargetSelector;
		self::$htmxIncludeSelector = $htmxIncludeSelector;
		self::$htmxGenerateTRIDField = $htmxGenerateTRIDField;
		
		//just use it now.
		$defaultOrderBy = zarr::toArray($defaultOrderBy);
		
		//start SQL command.
		$SQL = 'SELECT '.$selectFields.' FROM '.$DBtable.' WHERE 1 ' . $injectSQLwhere;
		
		//'order by' field processing.
		if(zs::isBlank(self::$orderBy) && !zs::isBlank($defaultOrderBy)) { self::$orderBy = $defaultOrderBy; } //use default array?
		
		//compute order BY
		$orderSQL = ' ';
		if(!zs::isBlank(self::$orderBy))
		{
			$orderSQL = ' ORDER BY ';
			$hasOrderBys = false;
			
			foreach(self::$orderBy as $key => $value)
			{
				if(zs::isBlank($key)) { continue; }
				$value = strtoupper($value);
				
				if($value == 'ASC' || $value == 'DESC')
				{
					if(in_array($key, self::$orderByFields)) { $orderSQL .= '`' . $key . '` ' . $value . ','; }
					else { zl::faultAbuse('Sent invalid sort field to orderBy: ' . $key); } //excuse me!
				}
				else { zl::faultAbuse('Sent invalid sort type to orderBy: ' . $value); } //how dare you!
				$hasOrderBys = true;
			}
			if($hasOrderBys) { $orderSQL = trim($orderSQL, ','); } else { $orderSQL = ' '; } //remove hanging chad
		}
		
		// you'll want to know this when developing.
		zl::quipD('zPTA Formed SQL query: '.$SQL.' '.$orderSQL);

		//pagination control
		if(self::$rowsPerPage != 0) //paginated mode?
		{
			//V---- just load one column for count function ( faster ).
			$x = explode(',', $selectFields); $shortSelectField = trim($x[0]);
			$SQLshort = str_replace(' ' . $selectFields . ' ', ' ' . $shortSelectField . ' ', $SQL);
			self::$rowsTotal = count(zdb::getArray($SQLshort));
			
			$data = zdb::getArray($SQL . $orderSQL . ' LIMIT ' . self::$offset . ',' . $rowsPerPage);
		}
		else
		{
			//unpaginated
			$data = zdb::getArray($SQL . $orderSQL);
			self::$rowsTotal = count($data);
		}
		
		//sanity check because this would silently bodge HTMX rows
		if(self::$rowsTotal != 0 && $htmxGenerateTRIDField != '' && !isset($data[0][$htmxGenerateTRIDField]))
		{ zl::fault('zPTA htmxGenerateTRIDField didn\'t match a field in the DB table. Cannot produce table.'); }
		
		//start the table wrapper
		echo '<div class="zPTA">';
		
		//return data so it can be processed line by line in the next step.
		return $data;
	}
	
	
	public static function output($searchBoxStyle = 'none')
	{
		//no data? no show.
		if(zs::isBlank(self::$rows)) { echo ('No ' . ucfirst(self::$name) . ' found.<br>'); return; }
		
		//table
		if(self::$degrade) //auto-generate grid-widths for degraded fields
		{
			$dWidths = '';
			foreach(self::$showFields as $k => $v)
			{
				if(isset(self::$degradeTHwidths[$k])){ $dWidths .= ' ' . self::$degradeTHwidths[$k]; }
				else { $dWidths .= ' auto'; }
			}
			?><style>#<?=self::$name?>.zlt_table.degrade tbody {grid-template-columns:<?=$dWidths?> !important;}</style><?php
			self::$extraWrapperClasses .= ' degrade';
		}
		
		echo PHP_EOL . '<table class="zlt_table ' . self::$extraWrapperClasses . '" id="' . self::$name . '">' . PHP_EOL;
        echo '<tbody class="test" hx-target="closest tr" hx-swap="outerHTML">';
		//echo "<tbody>"; //works without?
        echo '<tr class="zl_stickyT0">';
		
		//htmx mode?
		if(self::$htmxTargetSelector != '')
		{
			$linkString = '<a href="" hx-include="' . self::$htmxIncludeSelector . '" hx-indicator="' . self::$htmxTargetSelector . '" hx-target="' . self::$htmxTargetSelector . '" hx-get="?zpta=Y' . self::$injectLinkString . '&zOff=' . self::$offset . '&zOrd=';
		}
		else { $linkString = '<a href="?' . self::$injectLinkString . '&zOff=' . self::$offset . '&zOrd='; }
		
		//th tr
		foreach(self::$showFields as $key => $value)
		{
			//create sorting icons in header.
			if(in_array($key, self::$orderByFields))
			{
				if(isset(self::$orderBy[$key]))
				{
					if(self::$orderBy[$key] == 'DESC')
					{ $sortHead = $linkString . $key . '|ASC~">' . $value . zui::miconR('north', '', 'zl_right') . '</a>'; }
				    else if(self::$orderBy[$key] == 'ASC')
					{ $sortHead = $linkString . $key . '|DESC~">' . $value . zui::miconR('south', '', 'zl_right') . '</a>'; }
				    else
					{ $sortHead = $linkString . $key . '|DESC~">' . $value . zui::miconR('swap_vert', '', 'zl_right') . '</a>'; }
				}
				else { $sortHead = $linkString . $key . '|DESC~">' . $value . zui::miconR('swap_vert', '', 'zl_right') . '</a>'; }
			}
			else { $sortHead = $value; }
			
			//add colWidth if exists
			if(!zs::isBlank(self::$THfieldClasses[$key]))
			{ $class = ' class="' . self::$THfieldClasses[$key] . '"'; } else { $class = ''; }
			
			echo '<th' . $class . '>' . $sortHead . '</th>';
		}
	    echo '</tr>'.PHP_EOL;
		
		//TR TD
		foreach(self::$rows as $TR)
		{
			//for TR ID functionality.
			if(self::$htmxGenerateTRIDField != '') { $trid = ' ID = "' . $TR['rowData'][self::$htmxGenerateTRIDField] . '"'; } else { $trid = ''; }
		
			//show TR based class ( if hidden key is present )
			if(!zs::isBlank($TR['bgClasses'])) { echo '<tr class="' . $TR['bgClasses'] . '"' . $trid . '>'; }
			else { echo '<tr' . $trid . '>'; }
			
			//print TDs.
			foreach(self::$showFields as $key => $value)
			{
				//straight pipe string as HTML it if <td> typed; allows inserting things into the <td> field.
				if(zs::contains($TR['rowData'][$key], '<td')) { echo $TR['rowData'][$key]; }
				else //otherwise wrap in TD as normal
				{
					if(!zs::isBlank(self::$TDfieldClasses[$key])) //use td field classes logic
					{ echo '<td class="' . self::$TDfieldClasses[$key] . '">' . $TR['rowData'][$key] . '</td>'; }
					else { echo '<td>' . $TR['rowData'][$key] . '</td>'; } //no class
				}
			}
			
			echo '</tr>';
		}
		
		//print pagination row?
		if(self::$rowsPerPage != 0)
		{
			if(self::$htmxTargetSelector != '')
			{
				$linkString = '<a href= "" hx-include="' . self::$htmxIncludeSelector . '" hx-indicator="' . self::$htmxTargetSelector . '" hx-target="' . self::$htmxTargetSelector . '" hx-get="?zpta=Y' . self::$injectLinkString . '&zOrd=' .zarr::toAPipe(self::$orderBy);
			}
			else { $linkString = '<a href="?'.  self::$injectLinkString . '&zOrd=' .zarr::toAPipe(self::$orderBy); }
			
			echo self::paginate($linkString, self::$offset);
		}
		if(self::$degrade) { echo '</table>'; } //end table and put pagination on a different row.
		
		echo '</div>'; //end wrapper
	}
	
	//todo: forward arrow is currently broken in this function.
	//todo: page generation needs to be rewritten from scratch use use $page as the variable instead of SQL offset
	public static function paginate($linkString, $offset)
    {
		zui::bufStart();
		if(self::$degrade) { ?><table class="<?=self::$extraWrapperClasses?> zPTA_paginationT"><?php } //end table and put pagination on a different row.
		?>
	    <tr><th colspan="100%" class="zPTA_pagination"><div class="<?=self::$extraWrapperClasses?>"><?php
	    if(self::$degrade) { ?><div class="zl_left"><?php }
        /// bypass PREV link if currentPage is 0...
        if($offset != 0)
        {
            $prevPage = $offset - self::$rowsPerPage;
            echo $linkString . '&zOff='.$prevPage . '">◄</a>&nbsp;' . PHP_EOL;
        }

        // show links for all pages with results...
        $pages = ceil(self::$rowsTotal / self::$rowsPerPage);
        $offset = $offset / self::$rowsPerPage + 1;
        for($i = 1; $i <= $pages; $i++)
        {
            $newPage = self::$rowsPerPage * ($i - 1);
            if($pages > 100)
            {
                // do some intelligent skipping to avoid too many nav links
                if ($i % 10 == 0) { $tval = $offset - intval($i / 10) * 10; if($i % 100 != 0 && ($tval < -100 || $tval > 100)) continue; }
                else { $tval = $offset - intval($i / 10) * 10; if ($tval < 0 || $tval > 10) continue; }
            }
            else if($pages > 50)
            {
                // do some intelligent skipping to avoid too many nav links
                if ($i % 10 == 0) { $tval = $offset - intval($i / 10) * 10; if ($i % 50 != 0 && ($tval < -50 || $tval > 50)) continue; }
                else { $tval = $offset - intval($i / 10) * 10; if ($tval < 0 || $tval > 10) continue; }
            }
            
            if ($i == $offset) { echo '<span class="highlight">' . $i . '</span>&nbsp; '; } // highlight current page
            else { echo $linkString . '&zOff='.$newPage . '">' . $i . '</a>&nbsp;'.PHP_EOL; }
        }

        if(!(intval($offset / self::$rowsPerPage) == $pages - 1) && $pages != 0) //bypass 'next' link if on last page
        {
            $newPage = $offset + self::$rowsPerPage;
            echo $linkString . '&zOff=' . ($newPage - 1). '">►</a>&nbsp;' . PHP_EOL;
        }
		if(self::$degrade) { ?></div><?php }
        ?>
	        <div class="zl_right"> &nbsp; <?=self::$rowsTotal?> <?=ucfirst(self::$name)?></div>
	    </div>
	    </th></tr>
	    </table>
	    <?php
	    return zui::bufStop();
    }
}
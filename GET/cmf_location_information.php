<?php
/**
* Jomres CMS Agnostic Plugin
* @author  John m_majma@yahoo.com
* @version Jomres 9 
* @package Jomres
* @copyright 2017
* Jomres (tm) PHP files are released under both MIT and GPL2 licenses. This means that you can choose the license that best suits your project.
**/

// ################################################################
defined( '_JOMRES_INITCHECK' ) or die( '' );
// ################################################################

/*
	** Title | Mapping, get local item types
	** Description | Get the local item types, e.g. room types etc
*/


Flight::route('GET /cmf/location/information/@lat/@long', function($lat , $long )
	{
    require_once("../framework.php");

	validate_scope::validate('channel_management');
	
	cmf_utilities::validate_channel_for_user( );  // If the user and channel name do not correspond, then this channel is incorrect and can go no further, it'll throw a 204 error
	
	$lat = filter_var($lat, FILTER_SANITIZE_SPECIAL_CHARS);
	$long = filter_var($long, FILTER_SANITIZE_SPECIAL_CHARS);

	try {
		$client = new GuzzleHttp\Client();

		$response = $client->request('GET', 'https://nominatim.openstreetmap.org/reverse?format=json&lat='.$lat.'&lon='.$long , ['connect_timeout' => 4 , 'verify' => false , 'http_errors' => false] );
		$data = json_decode((string)$response->getBody());
		}
		catch (GuzzleHttp\Exception\RequestException $e) {
			//
			}

	$reply = new stdClass();
	$reply->country_code = strtoupper($data->address->country_code);
	$reply->region_id = find_region_id($data->address->state);
	if (is_null($reply->region_id)) { // It can take a while to setup the regions class (because of translations) so we'll only generate this data if the region_id wasn't found
		$jomres_regions = jomres_singleton_abstract::getInstance('jomres_regions');
		$regions = array();
		foreach ( $jomres_regions->regions as $region ) {
			if ( $region['countrycode'] == $reply->country_code ) {
				$region_id = $region['id'];
				$regions[ ] =  array("id"=> $region_id , "region_name" => $region['regionname']);
			}
		}
		
		$sfs = new SimpleFuzzySearch($regions, ["id" , "region_name"], $data->address->state );
		$results = $sfs->search();
		if ( isset($results[0][0]['id']) ) {
			$reply->region_id = $results[0][0]['id'];
		}
	}
	
	Flight::json( $response_name = "response" , $reply );
	});

	

/**
 * A Simple Fuzzy Search component using
 * Levenshtein Distance (LD) Algorithm and
 * Longest Common Substring (LCS) Algorithm.
 *
 * @author wataridori
 */



class SimpleFuzzySearch
{
    /**
     * @var array
     */
    protected $arrayData;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * @var string
     */
    protected $searchString;

    /**
     * @var float Max Levenshtein Distance Rate.
     */
    protected $maxLD = 0.3;

    /**
     * @var float Min Longest Common Substring Rate.
     */
    protected $minLCS = 0.7;

    const NOT_MATCH = 0;
    const STR2_STARTS_WITH_STR1 = 1;
    const STR2_CONTAINS_STR1 = 2;
    const STR1_STARTS_WITH_STR2 = 3;
    const STR1_CONTAINS_STR2 = 4;
    const LEVENSHTEIN_DISTANCE_CHECK = 5;
    const LONGEST_COMMON_SUBSTRING_CHECK = 6;

    /**
     * Constructor.
     *
     * @param array $arrayData
     * @param array|string $attribute
     * @param string|null $searchString
     */
    public function __construct($arrayData, $attribute, $searchString = null)
    {
        $this->arrayData = $arrayData;
        $this->attributes = is_array($attribute) ? $attribute : [$attribute];
        $this->searchString = $searchString;
    }

    /**
     * Get Max Levenshtein Distance Rate.
     *
     * @return float
     */
    public function getMaxLD()
    {
        return $this->maxLD;
    }

    /**
     * Set Max Levenshtein Distance Rate.
     *
     * @param float $ld
     */
    public function setMaxLD($ld)
    {
        $this->maxLD = $ld;
    }

    /**
     * Get Min Longest Common Substring Rate.
     *
     * @return float
     */
    public function getMinLCS()
    {
        return $this->minLCS;
    }

    /**
     * Set Min Longest Common Substring Rate.
     *
     * @param float $lcs
     */
    public function setMinLCS($lcs)
    {
        $this->minLCS = $lcs;
    }

    /**
     * Search using Levenshtein Distance (LD) Algorithm and
     * Longest Common Substring (LCS) Algorithm.
     *
     * @param string|null $searchString
     *
     * @return array
     */
    public function search($searchString = null)
    {
        $results = [];
        $search = $searchString ? strtolower($searchString) : strtolower($this->searchString);
        if (!$search) {
            return [];
        }
        foreach ($this->arrayData as $obj) {
            $found = false;
            foreach ($this->attributes as $attr) {
                if ($found || !isset($obj[$attr])) {
                    continue;
                }
                $val = strtolower($obj[$attr]);
                if (!$val) {
                    continue;
                }
                $type = self::NOT_MATCH;
                if (strpos($search, $val) !== false && strpos($search, $val) === 0) {
                    $type = self::STR2_STARTS_WITH_STR1;
                    $typeVal = strlen($val);
                } elseif (strpos($search, $val) > 0) {
                    $type = self::STR2_CONTAINS_STR1;
                    $typeVal = strlen($val);
                } elseif (strpos($val, $search) !== false && strpos($val, $search) === 0) {
                    $type = self::STR1_STARTS_WITH_STR2;
                    $typeVal = strlen($val);
                } elseif (strpos($val, $search) > 0) {
                    $type = self::STR1_CONTAINS_STR2;
                    $typeVal = strlen($val);
                } elseif ($this->checkLD($ld = levenshtein($val, $search), $search)) {
                    $type = self::LEVENSHTEIN_DISTANCE_CHECK;
                    $typeVal = $ld / strlen($search);
                } else {
                    $lcs = $this->getLCS($val, $search);
                    $similarPercent = strlen($lcs) / strlen($search);
                    if ($similarPercent > $this->minLCS) {
                        $type = self::LONGEST_COMMON_SUBSTRING_CHECK;
                        $typeVal = strlen($lcs) / strlen($val) * (-1);
                    }
                }
                if ($type !== self::NOT_MATCH) {
                    array_push($results, [$obj, $attr, $type, $typeVal]);
                    $found = true;
                }
            }
        }
        usort($results, [$this, 'sortArray']);

        return $results;
    }

    /**
     * Check whether Levenshtein Distance is small enough.
     *
     * @param int $ld
     * @param string $str
     *
     * @return bool
     */
    private function checkLD($ld, $str)
    {
        $length = strlen($str);
        if ($ld / $length <= $this->maxLD) {
            return true;
        }
        return false;
    }

    /**
     * Get Longest Common Substring.
     *
     * @param string $firstString
     * @param string $secondString
     *
     * @return string
     */
    private function getLCS($firstString, $secondString)
    {
        $firstStringLength = strlen($firstString);
        $secondStringLength = strlen($secondString);
        $return = '';

        if ($firstStringLength === 0 || $secondStringLength === 0) {
            return $return;
        }
        $longestCommonSubstring = [];
        for ($i = 0; $i < $firstStringLength; $i++) {
            $longestCommonSubstring[$i] = [];
            for ($j = 0; $j < $secondStringLength; $j++) {
                $longestCommonSubstring[$i][$j] = 0;
            }
        }
        $largestSize = 0;
        for ($i = 0; $i < $firstStringLength; $i++) {
            for ($j = 0; $j < $secondStringLength; $j++) {
                if ($firstString[$i] === $secondString[$j]) {
                    if ($i === 0 || $j === 0) {
                        $longestCommonSubstring[$i][$j] = 1;
                    } else {
                        $longestCommonSubstring[$i][$j] = $longestCommonSubstring[$i - 1][$j - 1] + 1;
                    }
                    if ($longestCommonSubstring[$i][$j] > $largestSize) {
                        $largestSize = $longestCommonSubstring[$i][$j];
                        $return = '';
                    }
                    if ($longestCommonSubstring[$i][$j] === $largestSize) {
                        $return = substr($firstString, $i - $largestSize + 1, $largestSize);
                    }
                }
            }
        }
        return $return;
    }

    /**
     * Sort arrays base on type and typeVal.
     *
     * @param array $firstArray
     * @param array $secondArray
     *
     * @return int
     */
    private function sortArray($firstArray, $secondArray)
    {
        if ($firstArray[2] === $secondArray[2]) {
            if ($firstArray[3] === $secondArray[3]) {
                return 0;
            } else {
                return $firstArray[3] < $secondArray[3] ? -1 : 1;
            }
        } else {
            return $firstArray[2] < $secondArray[2] ? -1 : 1;
        }
    }
}
	
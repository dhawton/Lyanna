<?php
/****************************************************
 * Copyright 2014 Daniel A. Hawton
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class WeatherController
{
    public static function getIndex()
    {
        return \Lyanna\View\View::make('Weather/Index');
    }

    public static function getAirport($airport = null)
    {
        global $app;

        if ($airport == null)
            throw new Exception("Airport cannot be null");

        $airports = array();
        $json = array();
        if (is_string($airport) && strpos($airport, ","))
            $airports = explode(",", $airport);
        elseif (is_array($airport))
            $airports = $airport;
        else
            $airports[] = $airport;

        foreach ($airports as $ap) {
            if (!preg_match('/^K[A-Z0-9]{3}$/', $ap)) { continue; }
            $data = $app->db->query('select')->table('weather')->where('facility', $ap)->limit(1)->execute()->current();
            if ($data->facility == null)
                $json[$ap] = "No data available";
            else
                $json[$ap] = array('rules' => $data->rules, 'wind' => $data->wind, 'altimeter' => $data->altimeter, 'metar' => $data->metar);
        }

        if (count($json) == 0) throw new Exception("No data to return");

        header("Content-Type: application/json");
        echo json_encode($json);
    }
} 
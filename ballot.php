<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cast your Preferences</title>

    <!-- CSS -->
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <?php
        // Connect to database
        require_once 'includes/connection.php';

        // Session variables
        session_start();

        // Stop access if they haven't registered
        if ($_SESSION["userValidated"] == false) {
            header("Location: /~s3844180/dba/asg4/voter-registration.php");
        }

        $electorate = $_SESSION["Electorate"];
        $election_code = $_SESSION["ElectionCode"];
        //$elecroate = 'Aston'; // Testing only, must call session electorate for final product
    ?>

    <?php        
        // Variables
        $candidate_array = array();
        $party_array = array();
        $errors = '';

        // Get eight candidates for this electorate
        $query = 'SELECT c.CANDIDATENAME, p.PARTYNAME FROM CANDIDATE c JOIN POLITICALPARTY p on c.PARTYCODE = p.PARTYCODE WHERE ELECTORATENAME LIKE :fn_bv';

        $candidate_statement = oci_parse($conn, $query);

        $fn = '%' . $electorate . '%';
        oci_bind_by_name($candidate_statement, ":fn_bv", $fn);

        oci_execute($candidate_statement);

        while ($row = oci_fetch_array($candidate_statement, OCI_ASSOC+OCI_RETURN_NULLS))
        {
            $candidate_array[] = $row['CANDIDATENAME'];
            $party_array[] = $row['PARTYNAME'];
        }

        // WHEN THEY VOTE
        if (isset($_POST['submit'])) {
            // Check that a ballot doesn't have duplicate ratings
            if ((empty($_POST["preference_1"])) || (empty($_POST["preference_2"])) || (empty($_POST["preference_3"])) || (empty($_POST["preference_4"])) || (empty($_POST["preference_5"])) || (empty($_POST["preference_6"])) || (empty($_POST["preference_7"])) || (empty($_POST["preference_8"]))) {
                $errors .= "You have not filled out all preferences. Please try again";
            }
            else {
                $preferenceRankings = array();
                @$preferenceRankings[] = $_POST["preference_1"] - 1;
                @$preferenceRankings[] = $_POST["preference_2"] - 1;
                @$preferenceRankings[] = $_POST["preference_3"] - 1;
                @$preferenceRankings[] = $_POST["preference_4"] - 1;
                @$preferenceRankings[] = $_POST["preference_5"] - 1;
                @$preferenceRankings[] = $_POST["preference_6"] - 1;
                @$preferenceRankings[] = $_POST["preference_7"] - 1;
                @$preferenceRankings[] = $_POST["preference_8"] - 1;
                echo '<pre>'; print_r($preferenceRankings); echo '</pre>';

                // Check for duplicate values
                if ($preferenceRankings != array_unique($preferenceRankings)) {
                    // Source: https://stackoverflow.com/questions/70652817/check-if-array-contains-a-duplicate-value-in-string-with-php
                    $errors .= "You have entered the same rating for two or more candidates. Please try again";
                }
                // Check for non-numerical values
                else if ((!is_numeric($_POST["preference_1"])) || (!is_numeric($_POST["preference_2"])) || (!is_numeric($_POST["preference_3"])) || (!is_numeric($_POST["preference_4"])) || (!is_numeric($_POST["preference_5"])) || (!is_numeric($_POST["preference_6"])) || (!is_numeric($_POST["preference_7"])) || (!is_numeric($_POST["preference_8"]))) {
                    $errors .= "You have entered non-numerical values into the ballot. Please try again";
                }
                // Otherwise, add preferences to ballot
                else {
                    // WORKING
                    // Sort candidates by ballot number
                    $candidate_rankings = array_combine($preferenceRankings, $candidate_array);
                    ksort($candidate_rankings);
                    //echo '<pre>'; print_r($candidate_rankings); echo '</pre>'; // DEBUGGING

                    // Give each candidate individual variable
                    $candidate_titles = array('candidate1', 'candidate2', 'candidate3', 'candidate4', 'candidate5', 'candidate6', 'candidate7', 'candidate8');
                    $individual_rankings = array_combine($candidate_titles, $candidate_rankings);
                    //echo '<pre>'; print_r($individual_rankings); echo '</pre>'; // DEBUGGING

                    foreach($individual_rankings as $key => $value) {
                        // Candidate will be saved as "candidate1", "candidate2", etc.
                        $$key = $value;
                    }

                    // INSERT BALLOT
                    // Query
                    $ballot_query = 'INSERT INTO Ballot '. 
                    'VALUES (:candidate1_bv, :candidate2_bv, :candidate3_bv, :candidate4_bv, :candidate5_bv, :candidate6_bv, :candidate7_bv, :candidate8_bv, :electioncode_bv, :electoratename_bv)';

                    $ballot_store = oci_parse($conn, $ballot_query);

                    // Bind variables
                    oci_bind_by_name($ballot_store, ":candidate1_bv", $candidate1);
                    oci_bind_by_name($ballot_store, ":candidate2_bv", $candidate2);
                    oci_bind_by_name($ballot_store, ":candidate3_bv", $candidate3);
                    oci_bind_by_name($ballot_store, ":candidate4_bv", $candidate4);
                    oci_bind_by_name($ballot_store, ":candidate5_bv", $candidate5);
                    oci_bind_by_name($ballot_store, ":candidate6_bv", $candidate6);
                    oci_bind_by_name($ballot_store, ":candidate7_bv", $candidate7);
                    oci_bind_by_name($ballot_store, ":candidate8_bv", $candidate8);
                    oci_bind_by_name($ballot_store, ":electioncode_bv", $election_code);
                    oci_bind_by_name($ballot_store, ":electoratename_bv", $electorate);

                    // Execute and get result
                    $ballot_result = oci_execute($ballot_store);

                    // FINAL STEP
                    # Check for errors
                    if (!$ballot_result) {
                        $errors = "We were not able to process your ballot at this time. Please try again";
                    }
                    // Otherwise,
                    else {
                        // Voting complete
                        session_destroy();
                        header("Location: /~s3844180/dba/asg4/thank-you.php");
                    }
                    
                    // TEST STUFF
                    // Source: https://stackoverflow.com/questions/36886305/php-sort-array-with-another-array
                    //$final_rankings = array_filter(array_replace(array_fill_keys($preferenceRankings, null), $candidate_array));
                    $final_rankings = array();
                    //foreach($preferenceRankings as $value) {
                    //    if(array_key_exists($value,$candidate_array)) {
                    //        $final_rankings[$value] = $candidate_array[$value];
                    //    }
                    //}
                    //echo '<pre>'; print_r($final_rankings); echo '</pre>';

                    // Create individual variables for candidates to add to query
                    //$candidate_titles = array('candidate1', 'candidate2', 'candidate3', 'candidate4', 'candidate5', 'candidate6', 'candidate7', 'candidate8');
                    //$candidate_rankings = array_combine($preferenceRankings, $candidate_array);
                    //ksort($candidate_rankings);
                    //echo '<pre>'; print_r($candidate_rankings); echo '</pre>';

                    //$ballot_values = json_encode($final_rankings);
                    //echo $ballot_values;

                    //foreach ($final_rankings as $candidate) {
                    //    ${$candidate} = $candidate;
                    //}
                }
            }

            // DEBUGGING ONLY - COMMENT OUT
            //echo '<pre>'; print_r($preferenceRankings); echo '</pre>';
            //echo '<pre>'; print_r($candidate_array); echo '</pre>';

            // Source: https://stackoverflow.com/questions/36886305/php-sort-array-with-another-array
            //$final_rankings = array_filter(array_replace(array_fill_keys($preferenceRankings, null), $candidate_array));

            //echo '<pre>'; print_r($final_rankings); echo '</pre>';
        }
    ?>
    <div class="ballot" id="ballot" name="ballot">
        <!--
            THIS WORKS ON THE PREMISE THAT EACH ELECTORATE HAS 8 CANDIDATES TO CHOOSE FROM
            BASED ON THE EXAMPLE SHOWN IN ASSIGNMENT SPECS.
        -->
        <br><img src="img/logo.png" width=150>
        <h2>Victoria</h2>
        <h2>Electoral Division of <?php echo $electorate ?></h2>
        <p style="color:#737373;">Number the boxes from 1 to 8 in the order of your choice.</p>
        <p class="error">* <?php echo $errors ?></p>
        
        <!-- PREFERENCES -->
        <form name="ballots" id="ballots" method="POST">
            <div id="candidate_1" name="candidate_1">
                <input type="text" class="preference-box" id="preference_1" name="preference_1" maxlength = "1">
                <p style="display: inline-block; text-align: left; padding-left:5px;" id="candidate1"><?php echo $candidate_array[0] . "<br>" . $party_array[0]?></p>
            </div>

            <div id="candidate_2" name="candidate_2">
                <input type="text" class="preference-box" id="preference_2" name="preference_2" maxlength = "1">
                <p style="display: inline-block; text-align: left; padding-left:5px;" id="candidate2"><?php echo $candidate_array[1] . "<br>" . $party_array[1]?></p>
            </div>

            <div id="candidate_3" name="candidate_3">
                <input type="text" class="preference-box" id="preference_3" name="preference_3" maxlength = "1">
                <p style="display: inline-block; text-align: left; padding-left:5px;" id="candidate3"><?php echo $candidate_array[2] . "<br>" . $party_array[2]?></p>
            </div>

            <div id="candidate_4" name="candidate_4">
                <input type="text" class="preference-box" id="preference_4" name="preference_4" maxlength = "1">
                <p style="display: inline-block; text-align: left; padding-left:5px;" id="candidate4"><?php echo $candidate_array[3] . "<br>" . $party_array[3]?></p>
            </div>

            <div id="candidate_5" name="candidate_5">
                <input type="text" class="preference-box" id="preference_5" name="preference_5" maxlength = "1">
                <p style="display: inline-block; text-align: left; padding-left:5px;" id="candidate5"><?php echo $candidate_array[4] . "<br>" . $party_array[4]?></p>
            </div>

            <div id="candidate_6" name="candidate_6">
                <input type="text" class="preference-box" id="preference_6" name="preference_6" maxlength = "1">
                <p style="display: inline-block; text-align: left; padding-left:5px;" id="candidate6"><?php echo $candidate_array[5] . "<br>" . $party_array[5]?></p>
            </div>

            <div id="candidate_7" name="candidate_7">
                <input type="text" class="preference-box" id="preference_7" name="preference_7" maxlength = "1">
                <p style="display: inline-block; text-align: left; padding-left:5px;" id="candidate7"><?php echo $candidate_array[6] . "<br>" . $party_array[6]?></p>
            </div>

            <div id="candidate_8" name="candidate_8">
                <input type="text" class="preference-box" id="preference_8" name="preference_8" maxlength = "1">
                <p style="display: inline-block; text-align: left; padding-left:5px;" id="candidate8"><?php echo $candidate_array[7] . "<br>" . $party_array[7]?></p>
            </div>
            <br>
            <input type="submit" name="submit" id="submit" class="sbm-button" value="Vote">
        </form>
    </div>
</body>
</html>
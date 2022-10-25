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
        //$election_code = $_SESSION["ElectionCode"];
        //$electorate = 'Aston'; // Testing only, must call session electorate for final product
    ?>

    <?php        
        // GET CANDIDATES AND PARTIES
        // Variables
        $candidate_array = array();
        $party_array = array();
        $errors = '';
        $numCandidates = '';
        $anyErrors = false;
        $preferenceRankings = array();
        $candidateRankings = array();

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
            $numCandidates++;
        }

        // GENERATE FORM
        // Beginning of form
        echo "<br><img src='img/logo.png' width=150>";
        echo "<h2>Victoria</h2>";
        echo "<h2>Electoral Division of " .$electorate . "</h2>";
        echo "<p style='color:#737373;'>Number the boxes from 1 to ". $numCandidates . " in the order of your choice.</p>";
        //echo "<p class='error'>* " . $errors  ."</p>";
        echo "<form name='ballots' id='ballots' method='POST'>";

        // Preference entry
        for ($i = 0; $i < $numCandidates; $i++) {
            $j = $i + 1;
            echo "<div id='candidate_$j' name='candidate_$j'>";
            echo "<input type='text' class='preference-box' id='preference_$j' name='preference_$j' maxlength = '1'>";
            echo "<p style='display: inline-block; text-align: left; padding-left:5px;' id='candidate$j'>$candidate_array[$i]  <br> $party_array[$i]";
            echo '</div>';
        }

        echo "<br>";
        echo "<input type='submit' name='submit' id='submit' class='sbm-button' value='Vote'>";
        echo "</form>";

        // WHEN THEY VOTE
        if (isset($_POST['submit'])) {
            // Ensure that all preferences are filled
            for ($i = 0; $i < $numCandidates; $i++) {
                $k = $i + 1;
                if ((empty($_POST["preference_$k"]))) {
                    $errors = "You have not filled out all preferences. Please try again";
                    $anyErrors = true;
                }
                else {
                    @$preferenceRankings[] = $_POST["preference_$k"];

                    // Check for duplicate values
                    if ($preferenceRankings != array_unique($preferenceRankings)) {
                        // Source: https://stackoverflow.com/questions/70652817/check-if-array-contains-a-duplicate-value-in-string-with-php
                        $errors = "You have entered the same rating for two or more candidates. Please try again";
                        $anyErrors = true;
                    }
                    // Check for non-numerical values
                    else if ((!is_numeric($_POST["preference_$k"])) ) {
                        $errors = "You have entered non-numerical values into the ballot. Please try again";
                        $anyErrors = true;
                    }
                }
            }

            if ($anyErrors == false) { 
                // Otherwise, add to ballot
                for ($i = 0; $i < $numCandidates; $i++) {
                    $canName = $candidate_array[$i];
                    $preferenceRank = $preferenceRankings[$i];
                    //$candidate_rankings = array_combine($preferenceRankings, $candidate_array);
                    $ballot_query = "INSERT INTO ballot_preference ". 
                    "VALUES (:candidate_name, :preference, :electorate)";

                    $ballot_store = oci_parse($conn, $ballot_query);
                    
                    oci_bind_by_name($ballot_store, ":candidate_name", $canName);
                    oci_bind_by_name($ballot_store, ":preference", $preferenceRank);
                    oci_bind_by_name($ballot_store, ":electorate", $electorate);

                    $ballot_result = oci_execute($ballot_store);

                    if (!$ballot_result) {
                        $errors = "We were not able to process your ballot at this time. Please try again";
                        $anyErrors = true;
                    }
                    // Otherwise,
                    else {
                        // Voting complete
                        session_destroy();
                        header("Location: /~s3844180/dba/asg4/thank-you.php");
                    }
                }
            }
            else {
                echo "<p class='error'>* " . $errors  ."</p>";
            }
        }

        // Output errors - if any
        //if ($anyErrors) {
        //    echo "<p class='error'>* " . $errors  ."</p>";
        //}
    ?>

    <style>
        .preference-box {
            text-align:center;
            height:45px;
            width:45px;
            color:black;
            font-size:25px;
            border:1px solid #737373;
        }
    </style>
</body>
</html>
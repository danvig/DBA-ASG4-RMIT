<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Federal Election Voting - Voter Identification</title>

    <!-- CSS -->
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <?php
        // Session variables
        session_start();

        // Stop access if they haven't completed step 1
        if ($_SESSION["step1"] == false) {
            header("Location: /~s3844180/dba/asg4/voter-registration.php");
        }

        $_SESSION["current_date"] = "";
        
        // Connect to database
        require_once 'includes/connection.php';
    ?>

    <!-- VALIDATING FORM -->
    <?php    
        // When the form is submitted
        if (isset($_POST['submit'])) {
            // VERIFY TIMESTAMP
            // Get current date for ballot issuance and ballot storing
            $currentDate = date('d-m-y'); // Get current date
            $formattedMonth = date('M', strtotime($currentDate)); // Convert month to name
            $formattedDate = date('d') . '-' . $formattedMonth . '-' . date('y'); // Concatenate into one date variable
            $_SESSION["currentDate"] = $formattedDate;
            // The above will format it correctly for the database


            // FINAL STEP
            if (isset($_POST['submit'])) {
                // STEP 3 - VOTED BEFORE?
                if(!isset($_POST['previous-vote'])) {
                    // ORIGINAL CODE
                    // If they haven't voted, proceed to step 4
                    $_SESSION["userValidated"] = true;
                }
                else {
                    // ORIGINAL CODE
                    // If they have voted, thank them and end the session to clear their data
                    session_destroy();
                    header("Location: /~s3844180/dba/asg4/thank-you.php");
                }

                // STEP 4 - ISSUE A BALLOT
                if ($_SESSION["userValidated"] == true)
                {
                    // Query to validate address so we can validate that the Voter Exists
                    $ballot_issuance_query = 'INSERT INTO Issuance '. 
                    'VALUES (:timestamp_bv, :pollingstation_bv, :voterid_bv, :electoratename_bv, :electioncode_bv)';

                    // Execute query
                    $ballot_issuance = oci_parse($conn, $ballot_issuance_query);

                    # Assign issuance variables
                    $timestamp = $_SESSION["currentDate"];
                    $pollingstation = 'Online Polling';
                    $issuance_voterid = $_SESSION["VoterID"];
                    $issuance_electoratename = $_SESSION["Electorate"];
                    $issuance_electioncode = $_SESSION["ElectionCode"];
                
                    # Bind issuance variables to insert statement
                    oci_bind_by_name($ballot_issuance, ":timestamp_bv", $timestamp);
                    oci_bind_by_name($ballot_issuance, ":pollingstation_bv", $pollingstation);
                    oci_bind_by_name($ballot_issuance, ":voterid_bv", $issuance_voterid);
                    oci_bind_by_name($ballot_issuance, ":electoratename_bv", $issuance_electoratename);
                    oci_bind_by_name($ballot_issuance, ":electioncode_bv", $issuance_electioncode);

                    # Execute query
                    $issuance_result = oci_execute($ballot_issuance);

                    # Check for errors - I.E. trigger stops insert because they've already voted
                    if (!$issuance_result) {
                        //$e = oci_error($ballot_issuance);
                        session_destroy(); // Make sure they can't go back to final step
                        header("Location: /~s3844180/dba/asg4/fraud-alert.php"); // Redirect to warning
                    }
                    else {
                        // Redirect to polling page
                        header("Location: /~s3844180/dba/asg4/ballot.php");
                        //echo "Done";
                    }
                }
            }
        }
    ?>

    <div id="registration">
        <form name="register" id="register" method="POST">
            <br><img src="img/logo.png" width=150>
            <h1>Electoral Role Search</h1>
            <h2 style="font-size:20px;">Hello <strong><?php echo strtoupper($_SESSION["VoterName"])?></strong> of Electorate <strong><?php echo strtoupper($_SESSION["Electorate"])?></strong></h2>
            
            <!-- Full Name -->
            <label for="name">Name:</label><br>
            <input type="text" id="name" name="name" class="input" placeholder="Enter your full name" value="<?php echo $_SESSION["VoterName"]; ?>" readonly>

            <br><br>

            <!-- Address --->
            <!-- I CHOSE TO DO WHOLE ADDRESS IN ONE FIELD AS IT'S HOW I FORMATTED MY DATABASE -->
            <label for="address">Address:</label><br>
            <input type="text" id="address" name="address" class="input" placeholder="Search address here..." value="<?php echo $_SESSION["address_line"]; ?>" readonly>

            <br><br>

            <!-- Suburb -->
            <label for="suburb">Suburb</label><br>
            <input type="text" id="suburb" name="suburb" class="input" value="<?php echo $_SESSION["suburb"]; ?>" readonly>

            <br><br>

            <!-- Suburb -->
            <label for="state">State</label><br>
            <input type="text" id="state" name="state" class="input" value="<?php echo $_SESSION["state"]; ?>" readonly>

            <br><br>

            <!-- Postcode -->
            <label for="postcode">Postcode</label><br>
            <input type="text" id="postcode" name="postcode" class="input" value="<?php echo $_SESSION["postcode"]; ?>" readonly>

            <br><br>

            <label for="previous-vote">Have you voted before in THIS election? (Tick if already voted)</label><br>
            <input type="checkbox" id="previous-vote" name="previous-vote">

            <br><br>
            <input type="submit" name="submit" id="submit" class="sbm-button">
        </form>
    </div>
</body>

</html>
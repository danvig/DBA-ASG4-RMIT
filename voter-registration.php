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
        $_SESSION["VoterID"] = "";
        $_SESSION["VoterName"] = "";
        $_SESSION["Electorate"] = "";
        $_SESSION["ElectionCode"] = "1";
        $_SESSION["Address"] = "";
        $_SESSION["address_line"] = "";
        $_SESSION["suburb"] = "";
        $_SESSION["state"] = "";
        $_SESSION["postcode"] = "";
        $_SESSION["step1"] = false; // If this is false, the system won't allow a user to access the final step for registration
        
        // Connect to database
        require_once 'includes/connection.php';
    ?>

    <!-- VALIDATING FORM -->
    <?php
        // Clear errors
        $nameErr = $addressErr = $voter_id = $address_line = '';
        $name_validated = $address_validated = $user_validated = false;
        $show_address_input = $show_final_input = false;

        // When the form is submitted
        if (isset($_POST['submit'])) {
            // STEP 1 - VALIDATE NAME
            if ($name_validated == false)
            {
                // VALIDATE NAME
                if(empty($_POST["name"])) // If the name field is empty
                {
                    $nameErr = "Required: Enter your first name";
                    $name = "";
                }
                else if(!preg_match("/^[a-zA-Z ]*$/", $_POST["name"])) // If it doesn't only contain letters and whitespace
                {
                    $nameErr = "Only Letters and whitespace allowed";
                    $name = "";
                }
                else
                {
                    $name = ($_POST["name"]); // If the name is valid, save it to a variable
                    $_SESSION["VoterName"] = ($_POST["name"]);
                }

                // When the name field is correct:
                if ($name != "") {
                    // Output an error, if any
                    $_POST["name-error"] = $nameErr;

                    // Query to get Voter ID so we can validate that the Voter Exists
                    $query = 'SELECT Voter_ID FROM Voter WHERE UPPER(name) LIKE UPPER(:fn_bv)';

                    // Execute query
                    $statement = oci_parse($conn, $query);

                    $fn = '%' . $_POST["name"] . '%';
                    oci_bind_by_name($statement, ":fn_bv", $fn);

                    oci_execute($statement);

                    while ($row = oci_fetch_array($statement, OCI_ASSOC+OCI_RETURN_NULLS))
                    {
                        foreach ($row as $voter_id)
                        {
                            #echo $voter_id;
                        }
                    }

                    #$row = oci_fetch_array($statement, OCI_ASSOC+OCI_RETURN_NULLS);

                    // If a record is found
                    if ($voter_id) {
                        $name_validated = true;
                        $show_address_input = true;
                        // Save as session variable to use across entire program
                        $_SESSION["VoterID"] = $voter_id;
                        //echo $voter_id;

                        // Get Electorate
                        $electorate_query = 'SELECT ELECTORATENAME FROM Voter WHERE VOTER_ID LIKE :fn_bv';

                        // Execute query
                        $electorate_statement = oci_parse($conn, $electorate_query);

                        //$fn = '%' . $voter_id . '%'; - CAUSED AN INTERESTING MINOR ERROR FOR VOTER ID 1, DON'T USE
                        $fn = $voter_id;
                        oci_bind_by_name($electorate_statement, ":fn_bv", $fn);

                        oci_execute($electorate_statement);

                        while ($electorate_result = oci_fetch_array($electorate_statement, OCI_ASSOC+OCI_RETURN_NULLS))
                        {
                            foreach ($electorate_result as $electorate_name)
                            {
                                // Save as session variable to use across entire program
                                $_SESSION["Electorate"] = $electorate_name;
                            }
                        }
                        //$_SESSION["Electorate"] = $electorate_name;

                        // FOR DEBUGGING, COMMENT IN AND OUT AS NEEDED
                        //echo $_SESSION["Electorate"];
                        //echo $_SESSION["VoterID"];
                    }
                    // If a record is not found
                    else {
                        $name_validated = false;
                        $nameErr = "You have not registered for this election event";
                    }
                }
            }

            // STEP 2 - VALIDATE ADDRESS
            if ($address_validated == false && $show_address_input == true)
            {
                if( (empty($_POST["address"])) || (empty($_POST["suburb"])) || (empty($_POST["state"])) || (empty($_POST["postcode"])) )
                {
                    $addressErr = "One or more fields is missing! Try again";
                }
                else
                {
                    // Make variables upper case to match input in Database.
                    $_SESSION["address_line"] = strtoupper(($_POST["address"]));
                    $_SESSION["suburb"] = strtoupper(($_POST["suburb"]));
                    $_SESSION["state"] = strtoupper(($_POST["state"]));
                    $_SESSION["postcode"] = strtoupper(($_POST["postcode"]));
                    $address_line = $_SESSION["address_line"] . ', ' . $_SESSION["suburb"] . ' ' . $_SESSION["state"] . ' ' . $_SESSION["postcode"];
                }

                if ($address_line != "") {
                    // Output an error, if any
                    $_POST["address-error"] = $addressErr;

                    // Query to validate address so we can validate that the Voter Exists
                    $address_query = 'SELECT residentialaddress FROM Voter WHERE UPPER(residentialaddress) LIKE UPPER(:fn_bv) AND VOTER_ID LIKE(:sn_bv)';

                    // Execute query
                    $address_statement = oci_parse($conn, $address_query);

                    $fn = '%' . $address_line . '%';
                    $sn = '%' . $_SESSION["VoterID"] . '%';
                    oci_bind_by_name($address_statement, ":fn_bv", $fn);
                    oci_bind_by_name($address_statement, ":sn_bv", $sn);

                    oci_execute($address_statement);

                    while ($address_result = oci_fetch_array($address_statement, OCI_ASSOC+OCI_RETURN_NULLS))
                    {
                        foreach ($address_result as $validated_address)
                        {
                            // Save in session variable for use across whole program
                            $_SESSION["Address"] = $validated_address;
                        }
                    }

                    if ($adddress_line = $_SESSION["Address"]) {
                        $address_validated = true;
                        // User is validated, take them to the final step
                        $_SESSION["step1"] = true;
                        header("Location: /~s3844180/dba/asg4/voter-registration-final.php");
                    }
                    else {
                        $address_validated = false;
                        $addressErr = "We could not confirm this address belongs to you";
                    }
                }
            }
        }
    ?>

    <div id="registration">
        <form name="register" id="register" method="POST">
            <br><img src="img/logo.png" width=150>
            <h1>Electoral Role Search</h1>
            
            <!-- Full Name -->
            <label for="name">Name:</label><br>
            <input type="text" id="name" name="name" class="input" placeholder="Enter your full name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name'], ENT_QUOTES) : ''; ?>">
            <p class="error" name="name-error" id="name-error">* <?php echo $nameErr; ?></p>

            <div id="address-inputs" class="<?php if ($name_validated == false) {echo "address-inputs";} else {echo ""; $show_address_input = true;} ?>">
                <br>

                <!-- Address --->
                <!-- I CHOSE TO DO WHOLE ADDRESS IN ONE FIELD AS IT'S HOW I FORMATTED MY DATABASE -->
                <label for="address">Address:</label><br>
                <input type="text" id="address" name="address" class="input" placeholder="Search address here..." value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address'], ENT_QUOTES) : ''; ?>">

                <br><br>

                <!-- Suburb -->
                <label for="suburb">Suburb</label><br>
                <input type="text" id="suburb" name="suburb" class="input" value="<?php echo isset($_POST['suburb']) ? htmlspecialchars($_POST['suburb'], ENT_QUOTES) : ''; ?>">

                <br><br>

                <!-- Suburb -->
                <label for="state">State</label><br>
                <input type="text" id="state" name="state" class="input" value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state'], ENT_QUOTES) : ''; ?>">

                <br><br>

                <!-- Postcode -->
                <label for="postcode">Postcode</label><br>
                <input type="text" id="postcode" name="postcode" class="input" value="<?php echo isset($_POST['postcode']) ? htmlspecialchars($_POST['postcode'], ENT_QUOTES) : ''; ?>">
                <p class="error" name="address-error" id="address-error">* <?php echo $addressErr; ?></p>
            </div>

            <br><br>

            <input type="submit" name="submit" id="submit" class="sbm-button">
        </form>

        <!--<form name="voted-before" id="voted-before" method="POST">
            <div id="voted-before-input" class="<?php if ($address_validated == false) {echo "voted-before-input";} else {echo "";} ?>">
                <br><br>

               oted Before 
                <label for="previous-vote">Have you voted before in THIS election? (Tick if already voted)</label><br>
                <input type="checkbox" id="previous-vote" name="previous-vote">

                <br><br>

                <button name="final-submit" id="final-submit" class="sbm-button">Confirm</button>

                <br><br>
            </div>
        </form>-->
    </div>
</body>

<!-- JAVASCRIPT FOR ADDRESS FINDER -->
<script>
(function() {
    var widget, initAddressFinder = function() {
        widget = new AddressFinder.Widget(
            document.getElementById('address'),
            'N8JURG4Y7P3T6D9KWCLA',
            'AU', {
                "address_params": {
                    "gnaf": "1"
                }
            }
        );

        widget.on('address:select', function(fullAddress, metaData) {
            // TODO - You will need to update these ids to match those in your form
            document.getElementById('address').value = metaData.address_line_1;
            document.getElementById('suburb').value = metaData.locality_name;
            document.getElementById('state').value = metaData.state_territory;
            document.getElementById('postcode').value = metaData.postcode;

        });


    };

    function downloadAddressFinder() {
        var script = document.createElement('script');
        script.src = 'https://api.addressfinder.io/assets/v3/widget.js';
        script.async = true;
        script.onload = initAddressFinder;
        document.body.appendChild(script);
    };

    document.addEventListener('DOMContentLoaded', downloadAddressFinder);
})();
</script>
</html>
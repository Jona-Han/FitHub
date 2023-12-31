<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../css/goals.css" />
    <title>Goals</title>
</head>

<body>

    <div class="header">
        <h1 style="color: orange;">Fitness Goals</h1>
        <div class="add-goal-button" onclick="showInputForm()" style="color: orange;">+</div>
        <a href="./goalsAndAchievements.php" class="back-button">Back</a>
    </div>

    <?php
    require_once("./dbUtils.php");
    // Establish a connection to the Oracle database
    $db_conn = OCILogon("ora_kyleetd", "a78242021", "dbhost.students.cs.ubc.ca:1522/stu");

    // Check if the connection was successful
    if (!$db_conn) {
        $e = oci_error();
        trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
    }

    // Define & execute SQL query
    $query = "SELECT * FROM User_FitnessGoal";
    $stmt = oci_parse($db_conn, $query);
    oci_execute($stmt);

    // Display table
    echo '<table>';
    echo '<tr><th>Goal ID</th><th>Description</th><th>Target Date</th><th>User ID</th><th>Action</th></tr>';

    $rowIndex = 0;
    while ($row = oci_fetch_assoc($stmt)) {
        echo '<tr>';
        echo '<td>' . $row['GOALID'] . '</td>';
        echo '<td>' . $row['DESCRIPTION'] . '</td>';
        echo '<td>' . $row['TARGETDATE'] . '</td>';
        echo '<td>' . $row['USERID'] . '</td>';
        echo "<td><button type='button' class='edit-button' data-row-index='$rowIndex'>Edit</button>";
        
        if (!$row['ACHIEVED'] == 1) {
        echo "<button form='row-form-$rowIndex' type='submit' name='achieved' value='" . $row['GOALID'] . "' class='ach-del-button'>Achieve</button>";
        }
        echo "<button form='row-form-$rowIndex' type='submit' name='delete' value='" . $row['GOALID'] . "' class='ach-del-button'>Delete</button></td>";
        echo '</tr>';

        echo "<form id='row-form-$rowIndex' method='post' action=''>";
        echo "<tr class='update-row' style='display: none;'>
            <td>&nbsp</td>
            <td><input type='text' name='update_list[DESCRIPTION]'></td>
            <td><input type='date' min='1900-01-01' max='9999-12-31' name='update_list[TARGETDATE]'></td>
            <td>&nbsp</td>
            <td>
                <button type='submit'>Update</button>
                <button type='button' class='cancel-button'>Cancel</button>
            </td>
            <input type='hidden' name='goalID' value='" . $row['GOALID'] . "'>
        </tr></form>";
    $rowIndex++;
    }

    echo '<form id="add-del" method="post" action="">'; // Add form element for add & delete functionality
    // Display input form row (last row) if '+' button is clicked
    echo '<tr id="form-row" class="hidden-row">';
    echo '<td>&nbsp</td>';
    echo '<td><input type="text" name="description" placeholder="Enter goal description" style="color: #5D3FD3;"></td>';
    echo '<td><input type="date" name="targetDate" min="1900-01-01" max="9999-12-31" style="color: #5D3FD3;"></td>';
    echo '<td><input type="number" name="userID" placeholder="Enter user ID" style="color: #5D3FD3;"></td>';
    echo '<td colspan="2">';
    echo '<input type="submit" name="submit" value="Add" style="background-color: #5D3FD3; color: #fff;"></td>';
    echo '</td>';
    echo '</tr>';
    echo '</table>'; 
    echo '</form>'; // Close the form element

    // Handle form submission
    if (isset($_POST['submit']) && !isset($_POST['update_list'])) {
        $description = $_POST['description'];
        $targetDate = $_POST['targetDate'];
        $userID = (int) $_POST['userID'];
    
        // Check if the user ID exists
        $checkQuery = "SELECT COUNT(*) AS USER_COUNT FROM Users WHERE ID = :userID";
        $checkStmt = oci_parse($db_conn, $checkQuery);
        oci_bind_by_name($checkStmt, ":userID", $userID);
        oci_execute($checkStmt);
        $row = oci_fetch_assoc($checkStmt);
        $userCount = (int) $row['USER_COUNT'];
    
        if ($userCount > 0) {
            // Insert goal in User_FitnessGoal table
            $insertQuery = "INSERT INTO User_FitnessGoal (DESCRIPTION, TARGETDATE, USERID) 
            VALUES (:description, TO_DATE(:targetDate, 'YYYY-MM-DD'), :userID)";
            $insertStmt = oci_parse($db_conn, $insertQuery);
            oci_bind_by_name($insertStmt, ":description", $description);
            oci_bind_by_name($insertStmt, ":targetDate", $targetDate);
            oci_bind_by_name($insertStmt, ":userID", $userID);
            oci_execute($insertStmt);
    
            echo '<script>window.location.href = window.location.href;</script>';
            exit();
        } else {
            echo '<div class="error-message">Invalid user ID. Please enter a valid user ID.</div>';
        }   
    } else if (isset($_POST['achieved'])) {
        $goalId = $_POST['achieved'];

        // Update User_FitnessGoal table to set ACHIEVED = 1
        $updateQuery = "UPDATE User_FitnessGoal SET ACHIEVED = 1 WHERE GOALID = :goalId";
        $updateStmt = oci_parse($db_conn, $updateQuery);
        oci_bind_by_name($updateStmt, ":goalId", $goalId);
        oci_execute($updateStmt);

        // Get goal information from User_FitnessGoal table
        $query = "SELECT * FROM User_FitnessGoal WHERE GOALID = :goalId";
        $stmt = oci_parse($db_conn, $query);
        oci_bind_by_name($stmt, ":goalId", $goalId);
        oci_execute($stmt);
        $goalRow = oci_fetch_assoc($stmt);

        // Insert goal into User_Achievement table
        $insertQuery = "INSERT INTO User_Achievement (DESCRIPTION, DATEACCOMPLISHED, USERID, GOALID) VALUES (:description, :dateAccomplished, :userID, :goalID)";
        $insertStmt = oci_parse($db_conn, $insertQuery);
        $todayDate = date("Y-m-d");
        oci_bind_by_name($insertStmt, ":description", $goalRow['DESCRIPTION']);
        oci_bind_by_name($insertStmt, ":dateAccomplished", $todayDate);
        oci_bind_by_name($insertStmt, ":userID", $goalRow['USERID']);
        oci_bind_by_name($insertStmt, ":goalID", $goalRow['GOALID']);
        oci_execute($insertStmt);

        echo '<script>window.location.href = window.location.href;</script>';
        exit();        

    } else if (isset($_POST['delete'])) {
        $goalId = $_POST['delete'];

        // Delete the goal from User_FitnessGoal table
        $deleteQuery = "DELETE FROM User_FitnessGoal WHERE goalID = :goalId";
        $deleteStmt = oci_parse($db_conn, $deleteQuery);
        oci_bind_by_name($deleteStmt, ":goalId", $goalId);
        oci_execute($deleteStmt);

        echo '<script>window.location.href = window.location.href;</script>';
        exit();
    } else if (isset($_POST['update_list'])) {
        $updates = $_POST['update_list'];
        $goalId = $_POST['goalID'];
    
        $updateVars = array(":goalID" => $goalId);
        $updateVars[":DESCRIPTION"] = $updates['DESCRIPTION'];
        $updateVars[":TARGETDATE"] = $updates['TARGETDATE'];
    
        $updateStatement = "UPDATE USER_FITNESSGOAL 
            SET DESCRIPTION = :DESCRIPTION, TARGETDATE = TO_DATE(:TARGETDATE, 'YYYY-MM-DD')";
            
        $query = $updateStatement . " WHERE GOALID = :goalID";
        executeBoundSQL($query, $updateVars);
        echo '<script>window.location.href = window.location.href;</script>';
    }

    // Close the database connection
    oci_free_statement($stmt);
    oci_close($db_conn);

    ?>

    <script>
        function showInputForm() {
            var formRow = document.getElementById('form-row');
            formRow.style.display = 'table-row';
        }

        // Add event listeners to the edit buttons and cancel buttons
        const editButtons = document.querySelectorAll('.edit-button');
        const cancelButtons = document.querySelectorAll('.cancel-button');
        const updateRows = document.querySelectorAll('.update-row');

        // Allow each edit button to reveal the hidden row
        editButtons.forEach((button, index) => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const rowIndex = button.getAttribute('data-row-index');
                updateRows[rowIndex].style.display = 'table-row';
            });
        });

        // Allow each cancel button to hide the row
        cancelButtons.forEach((button, index) => {
            button.addEventListener('click', () => {
                updateRows[index].style.display = 'none';
            });
        });
    </script>

</body>
</html>
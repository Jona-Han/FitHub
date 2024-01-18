# CPSC 304 Project Group 14
# FitHub

## 1. Summary
Our project aims to store and track data related to users’ fitness regimes. Users are composed of people looking to introduce an element of consistency and organization to their workouts such that they can clarify their progress and personal targets. The database models users, workouts, exercises, exercise logs, fitness goals, achievements, physical measurements, and gym locations.

## 2. ER Diagram
![diagram](https://github.com/Jona-Han/FitnessTracker/assets/87393036/e0ba9374-74ca-4688-b2da-ffd0deafca06)


## 3. Final Relational Schema

Users(ID: Integer, name: String)<br>
User_Achievement(achievementID: Integer, description: String, dateAccomplished: Date, achieved: Integer, userID: Integer goalID: Integer)<br>
User_FitnessGoal(goalID: Integer, targetDate: Date, description: String, achieved: Integer, userID: Integer)<br>
Workout(workoutID: Integer, name: String)<br>
Exercise(name: String)<br>
TrainingPlan(planID: INT, name: String, description: String)<br>
TrainingPlanConsistsOf(planID: Integer , exerciseName: String)<br>
Sees(userID: Integer, planID: Integer)<br>
CardioExercise(name: String, duration: Integer, speed: Integer)<br>
StrengthExercise(name: String, reps: Integer, weight: Integer, sets: Integer)<br>
FlexibilityExercise(name: String, duration: Integer, sets: Integer)<br>
Gym(address: String, postalCode: String, city: String, name: String)<br>
PCC(postalCode: String, country: String)<br>
User_Measurement(UserID: Integer, height: Integer, weight: Integer, BMI: Real)<br>
Completes(userID: Integer, workoutID: Integer, date: Date)<br>
Attends(address: String, postalCode: String, userID: Integer)<br>
AccomplishedBy(goalID: Integer, workoutID: Integer)<br>
ConsistsOf(workoutID: Integer, exerciseName: String)<br>

## 4. Query SQL Statements:

- INSERT (gym.php)<br><br>
INSERT INTO Gym (ADDRESS, POSTALCODE, CITY, NAME) VALUES (:address, :postalCode, :city, :name);<br>
INSERT INTO PCC (POSTALCODE, COUNTRY) VALUES (:postalCode, :country);<br>
INSERT INTO Attends (ADDRESS, POSTALCODE, USERID) VALUES (:address, :postalCode, :userID);<br>

- DELETE (goals.php)<br><br>
DELETE FROM User_FitnessGoal WHERE goalID = :goalId<br>

- UPDATE (goals.php)<br><br>
UPDATE USER_FITNESSGOAL SET DESCRIPTION = :DESCRIPTION, TARGETDATE =  TO_DATE(:TARGETDATE, 'YYYY-MM-DD')<br>

- SELECTION/PROJECTION (selectDataResult.php)<br><br>
dynamic query depending on user selected input<br>
"SELECT " . implode(", ", $selectedColumns) . " FROM $selectedTable WHERE ". implode(" AND ", $filterConditions)<br>

- JOIN (gym.php)<br><br>
SELECT Gym.address, Gym.postalCode, PCC.country, Gym.city, Gym.name, Attends.userID FROM Gym LEFT JOIN Attends ON Gym.address = Attends.address AND Gym.postalCode = Attends.postalCode LEFT JOIN PCC ON Gym.postalCode = PCC.postalCode

- AGGREGATION WITH GROUP BY (numberOfGymsPerCountry.php)<br><br>
SELECT PCC.country, COUNT(*) FROM PCC GROUP BY PCC.country

- AGGREGATION WITH HAVING (numUsersBMI.php)<br><br>
SELECT COUNT(*) AS user_count
            FROM (
                    SELECT Users.ID
                    FROM Users
                    JOIN User_Measurement ON User_Measurement.userID = Users.ID
                    GROUP BY Users.ID
                    HAVING MAX(User_Measurement.BMI) > :BMIValue
            ) subquery

- NESTED AGGREGATION WITH GROUP BY (averageBMI.php)<br><br>
CREATE VIEW TEMP(userID, average) AS
              SELECT u.userID, AVG(u.bmi) AS average
              FROM User_Measurement u
              GROUP BY userID
SELECT t.userID, u.name, t.average
          FROM Temp t
          JOIN Users u ON t.userID = u.ID
          WHERE t.average < (SELECT AVG(Temp.average) FROM TEMP)

- DIVISION (allUsersAllGyms.php)<br><br>
SELECT DISTINCT(U.ID) AS U_ID, U.name AS U_NAME
          FROM Users U
          JOIN Attends A ON U.ID = A.userID
          JOIN Gym G ON A.address = G.address AND A.postalCode = G.postalCode
          WHERE NOT EXISTS (
              SELECT G1.address, G1.postalCode
              FROM Gym G1
              WHERE NOT EXISTS (
                  SELECT A1.address, A1.postalCode
                  FROM Attends A1
                  WHERE A1.userID = U.ID
                  AND A1.address = G1.address
                  AND A1.postalCode = G1.postalCode
              )
          )

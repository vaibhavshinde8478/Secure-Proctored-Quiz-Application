Full-Stack Secure Online Examination Platform
A comprehensive, full-stack web application developed using PHP and MySQL, designed to simulate a real-world online examination environment with advanced proctoring and security features.

Project Overview
This platform provides a complete solution for creating, managing, and taking online quizzes. It features distinct roles for instructors and students, a robust group management system for assigning tests to specific classes, and advanced anti-cheating measures to ensure the integrity of the examination process.

Key Features
👨‍🏫 Instructor Features
Secure Login & Registration: Separate registration and login for instructors.

Profile Management: Instructors can update their username, email, and password.

Quiz Management:

Create quizzes with titles, descriptions, and time limits (start/end dates).

Set a specific duration (in minutes) for each quiz.

Delete entire quizzes and all associated data.

Question Management:

Add multiple-choice or true/false questions to any quiz.

Edit and delete existing questions.

Student Group Management:

Create and manage student groups (e.g., "11th Grade - Physics").

Add registered students to groups and remove them.

Quiz Assignment: Assign specific quizzes to one or more student groups.

Results Dashboard: View a list of all students who have completed a quiz, along with their scores and submission times.

Submission Review: Review the detailed submission of any student for a quiz they own.

👩‍🎓 Student Features
Secure Login & Registration: Separate registration and login for students.

Profile Management: Students can update their profile details and password.

Personalized Dashboard: Students see a list of only the quizzes that are currently active and assigned to their groups.

One-Time Attempt: Students can only take each quiz once. Completed quizzes are marked as such.

Timed Quizzes: Each quiz has a countdown timer that auto-submits the test when the time runs out.

Results & Review:

View a history of all completed quizzes and scores.

Review past submissions to see their selected answers alongside the correct answers.

🛡️ Advanced Proctoring & Security Features
Webcam & Microphone Access: Requires students to grant camera and microphone permission before starting a proctored quiz.

Live Face Detection: Uses face-api.js to monitor the student's webcam feed in real-time. If the student's face is not visible for more than 10 seconds, the quiz is automatically submitted.

Tab/Window Switching Detection:

Issues a one-time warning if the student switches to another tab or window.

Automatically submits the quiz on the second attempt to switch tabs.

Content Security: Disables right-clicking and text selection during a quiz to prevent copying of questions.

Technology Stack
Backend: PHP

Database: MySQL

Frontend: HTML5, CSS3, JavaScript (ES6+)

Face Detection: face-api.js library

Setup and Installation
This project is designed to run on a local server environment like XAMPP.

Prerequisites:

Install XAMPP or a similar local server stack (WAMP, MAMP).

Ensure Apache and MySQL services are running.

Clone the Repository:


Place Project in htdocs:

Move the cloned project folder into the htdocs directory of your XAMPP installation (e.g., C:\xampp\htdocs\quiz_platform).

Database Setup:

Open your web browser and navigate to http://localhost/phpmyadmin/.

Create a new database named quiz_platform.

Select the new database and go to the "Import" tab.

Upload and import the database.sql file provided in the repository to create all the necessary tables.

Run the Application:

Open your web browser and navigate to:

http://localhost/quiz_platform/

You can now register as an instructor and a student to test the full functionality.

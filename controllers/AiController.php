<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

class AiController extends Controller
{
    public function actionIndex()
    {
        $answer = '';

        if (Yii::$app->request->isPost) {

            $question = trim(
                Yii::$app->request->post('question', '')
            );

            if (empty($question)) {

                $answer = 'Please enter a question.';

            } else {

                $apiKey = getenv('GEMINI_API_KEY');

                if (!$apiKey) {

                    $answer = 'Gemini API key was not found.';

                } else {

                    $databaseContext = $this->getDatabaseContext($question);

                    $prompt = "
You are an AI Student Management Assistant.

You are connected to a Student Management System.

Answer the user's question naturally and clearly.

IMPORTANT RULES:
- Use the database information provided below for questions about students, courses, marks and attendance.
- Never invent student information.
- Never change or modify database information.
- If the requested information is not available, clearly say so.
- You can answer general educational questions using your own knowledge.
- Keep answers easy to understand.
- When presenting marks, include marks and total marks when available.
- When presenting student information, clearly mention the relevant fields.

DATABASE INFORMATION:
$databaseContext

USER QUESTION:
$question
";

                    $url =
                        'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent';

                    $data = [
                        'contents' => [
                            [
                                'parts' => [
                                    [
                                        'text' => $prompt
                                    ]
                                ]
                            ]
                        ]
                    ];

                    $jsonData = json_encode($data);

                    $ch = curl_init($url);

                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);

                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'x-goog-api-key: ' . $apiKey
                    ]);

                    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

                    $response = curl_exec($ch);

                    if ($response === false) {

                        $answer =
                            'Error connecting to Gemini: ' .
                            curl_error($ch);

                    } else {

                        $result = json_decode($response, true);

                        if (
                            isset(
                                $result['candidates'][0]['content']['parts'][0]['text']
                            )
                        ) {

                            $answer =
                                $result['candidates'][0]['content']['parts'][0]['text'];

                        } elseif (isset($result['error']['message'])) {

                            $answer =
                                'Gemini API Error: ' .
                                $result['error']['message'];

                        } else {

                            $answer =
                                'Gemini returned an unexpected response.';
                        }
                    }

                    curl_close($ch);
                }
            }
        }

        return $this->render('index', [
            'answer' => $answer,
        ]);
    }


    private function getDatabaseContext($question)
    {
        $questionLower = strtolower($question);

        $context = '';


        /*
         * ==========================================
         * COMPLETE STUDENT DETAILS
         * ==========================================
         */

        $students = \app\models\Student::find()->all();

        $foundStudent = null;

        foreach ($students as $student) {

            $studentName = strtolower(trim($student->name));

            if (
                $studentName !== '' &&
                strpos($questionLower, $studentName) !== false
            ) {

                $foundStudent = $student;
                break;
            }
        }


        /*
         * Student-specific question
         */

        if (
            $foundStudent &&
            (
                strpos($questionLower, 'details') !== false ||
                strpos($questionLower, 'information') !== false ||
                strpos($questionLower, 'profile') !== false ||
                strpos($questionLower, 'email') !== false ||
                strpos($questionLower, 'age') !== false ||
                strpos($questionLower, 'department') !== false
            )
        ) {

            $context .= "Student Details:\n";

            $context .=
                "ID: " .
                $foundStudent->id .
                "\n";

            $context .=
                "Name: " .
                $foundStudent->name .
                "\n";

            $context .=
                "Email: " .
                $foundStudent->email .
                "\n";

            $context .=
                "Age: " .
                $foundStudent->age .
                "\n";

            $context .=
                "Department: " .
                $foundStudent->department .
                "\n";
        }


        /*
         * ==========================================
         * ALL STUDENTS
         * ==========================================
         */

        if (
            strpos($questionLower, 'all students') !== false ||
            strpos($questionLower, 'list students') !== false ||
            strpos($questionLower, 'show students') !== false
        ) {

            if ($students) {

                $context .= "\nAll Students:\n";

                foreach ($students as $student) {

                    $context .=
                        "- ID: " .
                        $student->id .
                        ", Name: " .
                        $student->name .
                        ", Email: " .
                        $student->email .
                        ", Age: " .
                        $student->age .
                        ", Department: " .
                        $student->department .
                        "\n";
                }

            } else {

                $context .= "\nNo students are registered.\n";
            }
        }


        /*
         * ==========================================
         * STUDENT MARKS
         * ==========================================
         */

        if (
            strpos($questionLower, 'marks') !== false ||
            strpos($questionLower, 'grade') !== false ||
            strpos($questionLower, 'score') !== false
        ) {

            if ($foundStudent) {

                $marks = \app\models\Mark::find()
                    ->where([
                        'student_id' => $foundStudent->id
                    ])
                    ->all();

                if ($marks) {

                    $context .=
                        "\nMarks for " .
                        $foundStudent->name .
                        ":\n";

                    foreach ($marks as $mark) {

                        $courseName = 'Unknown Course';

                        if ($mark->course) {
                            $courseName =
                                $mark->course->course_name;
                        }

                        $percentage = 0;

                        if ($mark->total_marks > 0) {
                            $percentage =
                                round(
                                    ($mark->marks /
                                        $mark->total_marks) * 100,
                                    2
                                );
                        }

                        $context .=
                            "- Course: " .
                            $courseName .
                            ", Marks: " .
                            $mark->marks .
                            "/" .
                            $mark->total_marks .
                            ", Percentage: " .
                            $percentage .
                            "%\n";
                    }

                } else {

                    $context .=
                        "\nNo marks have been recorded for " .
                        $foundStudent->name .
                        ".\n";
                }

            } else {

                $context .=
                    "\nNo specific student was identified for the marks question.\n";
            }
        }


        /*
         * ==========================================
         * HIGHEST / LOWEST / AVERAGE MARKS
         * ==========================================
         */

        if (
            strpos($questionLower, 'highest marks') !== false ||
            strpos($questionLower, 'highest score') !== false ||
            strpos($questionLower, 'top student') !== false
        ) {

            $marksRecords = \app\models\Mark::find()->all();

            $highest = null;
            $highestStudent = null;

            foreach ($marksRecords as $mark) {

                if (
                    $highest === null ||
                    $mark->marks > $highest
                ) {

                    $highest = $mark->marks;

                    $highestStudent =
                        \app\models\Student::findOne(
                            $mark->student_id
                        );
                }
            }

            if ($highestStudent) {

                $context .=
                    "\nHighest Marks:\n" .
                    "Student: " .
                    $highestStudent->name .
                    "\nMarks: " .
                    $highest .
                    "/" .
                    $mark->total_marks .
                    "\n";
            }
        }


        if (
            strpos($questionLower, 'lowest marks') !== false ||
            strpos($questionLower, 'lowest score') !== false
        ) {

            $marksRecords = \app\models\Mark::find()->all();

            $lowest = null;
            $lowestStudent = null;
            $lowestTotal = 0;

            foreach ($marksRecords as $mark) {

                if (
                    $lowest === null ||
                    $mark->marks < $lowest
                ) {

                    $lowest = $mark->marks;
                    $lowestTotal = $mark->total_marks;

                    $lowestStudent =
                        \app\models\Student::findOne(
                            $mark->student_id
                        );
                }
            }

            if ($lowestStudent) {

                $context .=
                    "\nLowest Marks:\n" .
                    "Student: " .
                    $lowestStudent->name .
                    "\nMarks: " .
                    $lowest .
                    "/" .
                    $lowestTotal .
                    "\n";
            }
        }


        if (
            strpos($questionLower, 'average marks') !== false ||
            strpos($questionLower, 'average score') !== false
        ) {

            $marksRecords = \app\models\Mark::find()->all();

            if ($marksRecords) {

                $total = 0;
                $count = 0;

                foreach ($marksRecords as $mark) {

                    $total += $mark->marks;
                    $count++;
                }

                $average =
                    round($total / $count, 2);

                $context .=
                    "\nAverage Marks:\n" .
                    $average .
                    "\n";
            }
        }


        /*
         * ==========================================
         * PERFORMANCE SUMMARY
         * ==========================================
         */

        if (
            $foundStudent &&
            (
                strpos($questionLower, 'performance') !== false ||
                strpos($questionLower, 'overall') !== false ||
                strpos($questionLower, 'summary') !== false
            )
        ) {

            $marks = \app\models\Mark::find()
                ->where([
                    'student_id' => $foundStudent->id
                ])
                ->all();

            $context .=
                "\nPerformance information for " .
                $foundStudent->name .
                ":\n";

            if ($marks) {

                $totalObtained = 0;
                $totalPossible = 0;

                foreach ($marks as $mark) {

                    $totalObtained += $mark->marks;
                    $totalPossible += $mark->total_marks;
                }

                $overallPercentage = 0;

                if ($totalPossible > 0) {

                    $overallPercentage =
                        round(
                            ($totalObtained /
                                $totalPossible) * 100,
                            2
                        );
                }

                $context .=
                    "Total Marks: " .
                    $totalObtained .
                    "/" .
                    $totalPossible .
                    "\n";

                $context .=
                    "Overall Percentage: " .
                    $overallPercentage .
                    "%\n";

            } else {

                $context .=
                    "No marks available for performance analysis.\n";
            }
        }


        /*
         * ==========================================
         * ATTENDANCE
         * ==========================================
         */

        if (
            strpos($questionLower, 'attendance') !== false ||
            strpos($questionLower, 'absent') !== false ||
            strpos($questionLower, 'present') !== false
        ) {

            if ($foundStudent) {

                $attendance =
                    \app\models\Attendance::find()
                    ->where([
                        'student_id' => $foundStudent->id
                    ])
                    ->all();

                if ($attendance) {

                    $context .=
                        "\nAttendance for " .
                        $foundStudent->name .
                        ":\n";

                    foreach ($attendance as $record) {

                        $context .=
                            "- Date: " .
                            $record->attendance_date .
                            ", Status: " .
                            $record->status;

                        if (!empty($record->remarks)) {

                            $context .=
                                ", Remarks: " .
                                $record->remarks;
                        }

                        $context .= "\n";
                    }

                } else {

                    $context .=
                        "\nNo attendance records found for " .
                        $foundStudent->name .
                        ".\n";
                }
            }
        }


        /*
         * ==========================================
         * NUMBER OF STUDENTS
         * ==========================================
         */

        if (
            strpos($questionLower, 'how many students') !== false ||
            strpos($questionLower, 'number of students') !== false ||
            strpos($questionLower, 'total students') !== false
        ) {

            $count =
                \app\models\Student::find()->count();

            $context .=
                "\nTotal students registered: " .
                $count .
                "\n";
        }


        /*
         * ==========================================
         * COURSES
         * ==========================================
         */

        if (
            strpos($questionLower, 'course') !== false
        ) {

            $courses =
                \app\models\Course::find()->all();

            if ($courses) {

                $context .=
                    "\nAvailable Courses:\n";

                foreach ($courses as $course) {

                    $context .=
                        "- " .
                        $course->course_name .
                        " (" .
                        $course->course_code .
                        ")\n";
                }

            } else {

                $context .=
                    "\nNo courses are currently registered.\n";
            }
        }


        /*
         * ==========================================
         * GENERAL QUESTION
         * ==========================================
         */

        if (empty($context)) {

            $context =
                "No specific database information was required for this question.";
        }

        return $context;
    }
}
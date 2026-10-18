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

                    /*
                     * Get only the database information
                     * required for the user's question.
                     */
                    $databaseContext =
                        $this->getDatabaseContext($question);
                        Yii::info(
    'Database Context Size: ' . strlen($databaseContext) . ' characters',
    'ai_transport'
);

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

                    curl_setopt(
                        $ch,
                        CURLOPT_RETURNTRANSFER,
                        true
                    );

                    curl_setopt(
                        $ch,
                        CURLOPT_POST,
                        true
                    );

                    curl_setopt(
                        $ch,
                        CURLOPT_HTTPHEADER,
                        [
                            'Content-Type: application/json',
                            'x-goog-api-key: ' . $apiKey
                        ]
                    );

                    curl_setopt(
                        $ch,
                        CURLOPT_POSTFIELDS,
                        $jsonData
                    );

                    $response = curl_exec($ch);

                    if ($response === false) {

                        $answer =
                            'Error connecting to Gemini: ' .
                            curl_error($ch);

                    } else {

                        $result =
                            json_decode($response, true);

                        if (
                            isset(
                                $result['candidates'][0]['content']['parts'][0]['text']
                            )
                        ) {

                            $answer =
                                $result['candidates'][0]['content']['parts'][0]['text'];

                        } elseif (
                            isset($result['error']['message'])
                        ) {

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
         * STUDENT IDENTIFICATION
         * ==========================================
         */

        $foundStudent = null;

        /*
         * First try exact generated student names.
         *
         * Example:
         * "What is Test Student 46's performance?"
         *
         * Searches for:
         * Test Student 46
         */

        if (
            preg_match(
                '/\btest\s+student\s+(\d+)\b/i',
                $question,
                $match
            )
        ) {

            $studentNumber = (int) $match[1];

            $foundStudent =
                \app\models\Student::find()
                ->where([
                    'name' => 'Test Student ' . $studentNumber
                ])
                ->one();
        }


        /*
         * General student name search.
         *
         * This is used only when an exact
         * "Test Student XX" pattern was not found.
         *
         * The query is always limited to 5 candidates.
         */

        if (!$foundStudent) {

            $words = preg_split(
                '/\s+/',
                strtolower($question)
            );

            $cleanWords = [];

            foreach ($words as $word) {

                $word = preg_replace(
                    '/[^a-zA-Z0-9]/',
                    '',
                    $word
                );

                if (strlen($word) >= 3) {
                    $cleanWords[] = $word;
                }
            }

            $wordCount = count($cleanWords);

            for (
                $length = min(4, $wordCount);
                $length >= 2 && !$foundStudent;
                $length--
            ) {

                for (
                    $i = 0;
                    $i <= $wordCount - $length;
                    $i++
                ) {

                    $phraseWords = array_slice(
                        $cleanWords,
                        $i,
                        $length
                    );

                    $query =
                        \app\models\Student::find();

                    foreach ($phraseWords as $word) {

                        $query->andWhere([
                            'like',
                            'name',
                            $word
                        ]);
                    }

                    $candidates =
                        $query
                        ->limit(5)
                        ->all();

                    foreach ($candidates as $candidate) {

                        $candidateName =
                            strtolower($candidate->name);

                        if (
                            strpos(
                                strtolower($question),
                                $candidateName
                            ) !== false
                        ) {

                            $foundStudent =
                                $candidate;

                            break 2;
                        }
                    }
                }
            }
        }


        /*
         * ==========================================
         * STUDENT DETAILS
         * ==========================================
         */

        if (
            $foundStudent &&
            (
                strpos(
                    $questionLower,
                    'details'
                ) !== false ||

                strpos(
                    $questionLower,
                    'information'
                ) !== false ||

                strpos(
                    $questionLower,
                    'profile'
                ) !== false ||

                strpos(
                    $questionLower,
                    'email'
                ) !== false ||

                strpos(
                    $questionLower,
                    'age'
                ) !== false ||

                strpos(
                    $questionLower,
                    'department'
                ) !== false
            )
        ) {

            $context .=
                "Student Details:\n";

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
         *
         * Never retrieve the complete student table.
         *
         * Maximum 50 students are retrieved.
         */

        if (
            strpos(
                $questionLower,
                'all students'
            ) !== false ||

            strpos(
                $questionLower,
                'list students'
            ) !== false ||

            strpos(
                $questionLower,
                'show students'
            ) !== false
        ) {

            $students =
                \app\models\Student::find()
                ->select([
                    'id',
                    'name',
                    'department'
                ])
                ->orderBy([
                    'id' => SORT_ASC
                ])
                ->limit(50)
                ->asArray()
                ->all();

            if ($students) {

                $context .=
                    "\nStudents List (first 50):\n";

                foreach ($students as $student) {

                    $context .=
                        "- ID: " .
                        $student['id'] .
                        ", Name: " .
                        $student['name'] .
                        ", Department: " .
                        $student['department'] .
                        "\n";
                }

                $context .=
                    "Only the first 50 students were retrieved.\n";

            } else {

                $context .=
                    "\nNo students are currently registered.\n";
            }
        }


        /*
         * ==========================================
         * STUDENT MARKS
         * ==========================================
         *
         * Retrieve only marks for the requested student.
         *
         * Maximum 100 records.
         *
         * Course information is obtained using JOIN,
         * so extra course queries are avoided.
         */

        if (
            strpos($questionLower, 'mark') !== false ||
            strpos($questionLower, 'result') !== false ||
            strpos($questionLower, 'score') !== false
        ) {

            if ($foundStudent) {

                $marks =
                    \app\models\Mark::find()
                    ->alias('m')
                    ->select([
                        'marks' =>
                            'm.marks',

                        'total_marks' =>
                            'm.total_marks',

                        'course_name' =>
                            'c.course_name',

                        'course_code' =>
                            'c.course_code',
                    ])
                    ->leftJoin(
                        [
                            'c' =>
                                \app\models\Course::tableName()
                        ],
                        'c.id = m.course_id'
                    )
                    ->where([
                        'm.student_id' =>
                            $foundStudent->id
                    ])
                    ->limit(100)
                    ->asArray()
                    ->all();

                if ($marks) {

                    $context .=
                        "\nMarks for " .
                        $foundStudent->name .
                        ":\n";

                    foreach ($marks as $mark) {

                        $courseName =
                            $mark['course_name']
                            ?? 'Unknown Course';

                        $courseCode =
                            $mark['course_code']
                            ?? '';

                        $context .=
                            "- Course: " .
                            $courseName .

                            (
                                $courseCode
                                ? " (" .
                                  $courseCode .
                                  ")"
                                : ""
                            ) .

                            ", Marks: " .
                            $mark['marks'] .
                            "/" .
                            $mark['total_marks'] .
                            "\n";
                    }

                    if (count($marks) >= 100) {

                        $context .=
                            "Only the first 100 mark records were retrieved.\n";
                    }

                } else {

                    $context .=
                        "\nNo marks found for " .
                        $foundStudent->name .
                        ".\n";
                }
            }
        }


        /*
         * ==========================================
         * HIGHEST MARKS
         * ==========================================
         *
         * First find the highest score.
         *
         * Then retrieve maximum 100 students
         * having that score using ONE JOIN query.
         */

        if (
            strpos(
                $questionLower,
                'highest marks'
            ) !== false ||

            strpos(
                $questionLower,
                'highest score'
            ) !== false ||

            strpos(
                $questionLower,
                'top student'
            ) !== false
        ) {

            $highestMarks =
                \app\models\Mark::find()
                ->select([
                    'marks',
                    'total_marks'
                ])
                ->orderBy([
                    'marks' => SORT_DESC
                ])
                ->limit(1)
                ->asArray()
                ->one();

            if ($highestMarks) {

                $maxMarks =
                    $highestMarks['marks'];

                $highestStudents =
                    \app\models\Mark::find()
                    ->alias('m')
                    ->select([
                        'student_id' =>
                            'm.student_id',

                        'marks' =>
                            'm.marks',

                        'total_marks' =>
                            'm.total_marks',

                        'student_name' =>
                            's.name',

                        'department' =>
                            's.department',
                    ])
                    ->innerJoin(
                        [
                            's' =>
                                \app\models\Student::tableName()
                        ],
                        's.id = m.student_id'
                    )
                    ->where([
                        'm.marks' =>
                            $maxMarks
                    ])
                    ->limit(100)
                    ->asArray()
                    ->all();

                $context .=
                    "\nHighest Marks:\n" .
                    "Highest Score: " .
                    $maxMarks .
                    "/" .
                    $highestMarks['total_marks'] .
                    "\n";

                foreach (
                    $highestStudents
                    as $student
                ) {

                    $context .=
                        "- Student: " .
                        $student['student_name'] .
                        " (ID: " .
                        $student['student_id'] .
                        "), Department: " .
                        $student['department'] .
                        "\n";
                }

                if (
                    count($highestStudents) >= 100
                ) {

                    $context .=
                        "Only the first 100 students with the highest score were retrieved.\n";
                }
            }
        }


        /*
         * ==========================================
         * LOWEST MARKS
         * ==========================================
         */

        if (
            strpos(
                $questionLower,
                'lowest marks'
            ) !== false ||

            strpos(
                $questionLower,
                'lowest score'
            ) !== false
        ) {

            $lowestMark =
                \app\models\Mark::find()
                ->orderBy([
                    'marks' => SORT_ASC
                ])
                ->limit(1)
                ->one();

            if ($lowestMark) {

                $lowestStudent =
                    \app\models\Student::findOne(
                        $lowestMark->student_id
                    );

                if ($lowestStudent) {

                    $context .=
                        "\nLowest Marks:\n" .
                        "Student: " .
                        $lowestStudent->name .
                        "\nMarks: " .
                        $lowestMark->marks .
                        "/" .
                        $lowestMark->total_marks .
                        "\n";
                }
            }
        }


        /*
         * ==========================================
         * AVERAGE MARKS
         * ==========================================
         *
         * Database calculates the average.
         * No mark records are retrieved.
         */

        if (
            strpos(
                $questionLower,
                'average marks'
            ) !== false ||

            strpos(
                $questionLower,
                'average score'
            ) !== false
        ) {

            $average =
                \app\models\Mark::find()
                ->average('marks');

            if ($average !== null) {

                $average =
                    round($average, 2);

                $context .=
                    "\nAverage Marks:\n" .
                    $average .
                    "\n";

            } else {

                $context .=
                    "\nNo marks are available to calculate an average.\n";
            }
        }


        /*
         * ==========================================
         * PERFORMANCE SUMMARY
         * ==========================================
         *
         * SUM is calculated directly by the database.
         *
         * We do NOT retrieve all mark records.
         */

        if (
            $foundStudent &&
            (
                strpos(
                    $questionLower,
                    'performance'
                ) !== false ||

                strpos(
                    $questionLower,
                    'overall'
                ) !== false ||

                strpos(
                    $questionLower,
                    'summary'
                ) !== false
            )
        ) {

            $result =
                \app\models\Mark::find()
                ->select([
                    'total_obtained' =>
                        new \yii\db\Expression(
                            'SUM(marks)'
                        ),

                    'total_possible' =>
                        new \yii\db\Expression(
                            'SUM(total_marks)'
                        )
                ])
                ->where([
                    'student_id' =>
                        $foundStudent->id
                ])
                ->asArray()
                ->one();

            $totalObtained =
                $result['total_obtained'] ?? 0;

            $totalPossible =
                $result['total_possible'] ?? 0;

            if ($totalPossible > 0) {

                $overallPercentage =
                    round(
                        (
                            $totalObtained /
                            $totalPossible
                        ) * 100,
                        2
                    );

                $context .=
                    "\nPerformance information for " .
                    $foundStudent->name .
                    ":\n" .
                    "Total Marks: " .
                    $totalObtained .
                    "/" .
                    $totalPossible .
                    "\n" .
                    "Overall Percentage: " .
                    $overallPercentage .
                    "%\n";

            } else {

                $context .=
                    "\nNo marks available for performance analysis.\n";
            }
        }


        /*
         * ==========================================
         * ATTENDANCE
         * ==========================================
         *
         * One database query calculates:
         *
         * Total
         * Present
         * Absent
         *
         * We do NOT retrieve attendance rows.
         */

        if (
            strpos(
                $questionLower,
                'attendance'
            ) !== false ||

            strpos(
                $questionLower,
                'absent'
            ) !== false ||

            strpos(
                $questionLower,
                'present'
            ) !== false
        ) {

            if ($foundStudent) {

                $attendanceSummary =
                    \app\models\Attendance::find()
                    ->select([

                        'total' =>
                            new \yii\db\Expression(
                                'COUNT(*)'
                            ),

                        'present' =>
                            new \yii\db\Expression(
                                "SUM(
                                    CASE
                                        WHEN status = 'Present'
                                        THEN 1
                                        ELSE 0
                                    END
                                )"
                            ),

                        'absent' =>
                            new \yii\db\Expression(
                                "SUM(
                                    CASE
                                        WHEN status = 'Absent'
                                        THEN 1
                                        ELSE 0
                                    END
                                )"
                            ),
                    ])
                    ->where([
                        'student_id' =>
                            $foundStudent->id
                    ])
                    ->asArray()
                    ->one();

                $totalAttendance =
                    (int) (
                        $attendanceSummary['total']
                        ?? 0
                    );

                $presentCount =
                    (int) (
                        $attendanceSummary['present']
                        ?? 0
                    );

                $absentCount =
                    (int) (
                        $attendanceSummary['absent']
                        ?? 0
                    );

                if ($totalAttendance > 0) {

                    $attendancePercentage =
                        round(
                            (
                                $presentCount /
                                $totalAttendance
                            ) * 100,
                            2
                        );

                    $context .=
                        "\nAttendance Summary for " .
                        $foundStudent->name .
                        ":\n" .

                        "Total Attendance Records: " .
                        $totalAttendance .
                        "\n" .

                        "Present: " .
                        $presentCount .
                        "\n" .

                        "Absent: " .
                        $absentCount .
                        "\n" .

                        "Attendance Percentage: " .
                        $attendancePercentage .
                        "%\n";

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
         *
         * COUNT only.
         *
         * No student records are retrieved.
         */

        if (
            strpos(
                $questionLower,
                'how many students'
            ) !== false ||

            strpos(
                $questionLower,
                'number of students'
            ) !== false ||

            strpos(
                $questionLower,
                'total students'
            ) !== false
        ) {

            $count =
                \app\models\Student::find()
                ->count();

            $context .=
                "\nTotal students registered: " .
                $count .
                "\n";
        }


        /*
         * ==========================================
         * COURSES
         * ==========================================
         *
         * Count all courses, but retrieve
         * maximum 20 course records.
         */

        if (
            strpos(
                $questionLower,
                'course'
            ) !== false
        ) {

            $courseCount =
                \app\models\Course::find()
                ->count();

            if ($courseCount > 0) {

                $context .=
                    "\nTotal Number of Courses: " .
                    $courseCount .
                    "\n";

                $courses =
                    \app\models\Course::find()
                    ->select([
                        'id',
                        'course_name',
                        'course_code'
                    ])
                    ->orderBy([
                        'id' => SORT_ASC
                    ])
                    ->limit(20)
                    ->asArray()
                    ->all();

                if ($courses) {

                    $context .=
                        "Sample of Available Courses:\n";

                    foreach (
                        $courses as $course
                    ) {

                        $context .=
                            "- " .
                            $course['course_name'] .
                            " (" .
                            $course['course_code'] .
                            ")\n";
                    }

                    if ($courseCount > 20) {

                        $context .=
                            "Only 20 courses were retrieved from the database.\n";
                    }
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

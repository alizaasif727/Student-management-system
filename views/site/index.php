<div class="site-index">

    <div class="jumbotron text-center">
        <h1>Student Management System</h1>

        <p class="lead">
            Welcome to the Student Management System
        </p>
    </div>

    <div class="row">

        <div class="col-md-3">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    Students
                </div>

                <div class="panel-body text-center">
                    <h2><?= $studentCount ?></h2>

                    <a href="<?= \yii\helpers\Url::to(['/student/index']) ?>"
                       class="btn btn-primary">
                        Manage Students
                    </a>
                </div>
            </div>
        </div>


        <div class="col-md-3">
            <div class="panel panel-success">
                <div class="panel-heading">
                    Courses
                </div>

                <div class="panel-body text-center">
                    <h2><?= $courseCount ?></h2>

                    <a href="<?= \yii\helpers\Url::to(['/course/index']) ?>"
                       class="btn btn-success">
                        Manage Courses
                    </a>
                </div>
            </div>
        </div>


        <div class="col-md-3">
            <div class="panel panel-warning">
                <div class="panel-heading">
                    Marks
                </div>

                <div class="panel-body text-center">
                    <h2><?= $markCount ?></h2>

                    <a href="<?= \yii\helpers\Url::to(['/mark/index']) ?>"
                       class="btn btn-warning">
                        Manage Marks
                    </a>
                </div>
            </div>
        </div>


        <div class="col-md-3">
            <div class="panel panel-info">
                <div class="panel-heading">
                    Attendance
                </div>

                <div class="panel-body text-center">
                    <h2><?= $attendanceCount ?></h2>

                    <a href="<?= \yii\helpers\Url::to(['/attendance/index']) ?>"
                       class="btn btn-info">
                        Manage Attendance
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>
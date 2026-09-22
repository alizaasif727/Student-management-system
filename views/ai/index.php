<div class="site-index">

    <div class="jumbotron text-center">
        <h1>AI Student Assistant</h1>

        <p class="lead">
            Ask questions about students, courses, marks, and attendance.
        </p>
    </div>

    <div class="panel panel-primary">

        <div class="panel-heading">
            Ask the AI Assistant
        </div>

        <div class="panel-body">

            <form method="post" action="<?= \yii\helpers\Url::to(['/ai/index']) ?>">

    <?= \yii\helpers\Html::hiddenInput(
        Yii::$app->request->csrfParam,
        Yii::$app->request->csrfToken
    ) ?>

    <div class="form-group">

                    <label for="question">
                        Your Question
                    </label>

                    <input
                        type="text"
                        name="question"
                        id="question"
                        class="form-control"
                        placeholder="Example: How many students are registered?"
                        required
                    >

                </div>

                <br>

                <button type="submit" class="btn btn-primary">
                    Ask AI
                </button>

            </form>

            <?php if (!empty($answer)): ?>

                <hr>

                <h4>Assistant Response</h4>

                <div class="alert alert-info">
                    <?= \yii\helpers\Html::encode($answer) ?>
                </div>

            <?php endif; ?>

        </div>

    </div>

</div>
<html>
    <head>
        <link rel="stylesheet" href="assets/css/progress-bar.css" />
    </head>
    <body>
        <div class="progress-tracker">
            <div class="progress-line">
                <div class="progress-line-fill" id="progressLineFill"></div>
            </div>
            <div class="progress-steps">
                <div class="progress-step " data-step="1">
                    <a href="select-domain.php">
                        <div class="step-icon">
                            <i class="fas fa-globe"></i>
                            <div class="step-check"><i class="fas fa-check"></i></div>
                        </div>
                    </a>
                    <div class="step-content">
                        <div class="step-title">Domain</div>
                        <div class="step-subtitle">Select category</div>
                    </div>
                </div>
                <div class="progress-step active" data-step="2">
                    <a href="select-field.php?domain_id=<?php $_GET['domain_id'] ?>">
                        <div class="step-icon">
                            <i class="fas fa-sitemap"></i>
                            <div class="step-check"><i class="fas fa-check"></i></div>
                        </div>
                    </a>
                    <div class="step-content">
                        <div class="step-title">Field</div>
                        <div class="step-subtitle">Choose specialty</div>
                    </div>
                </div>
                <div class="progress-step" data-step="3">
                    <div class="step-icon">
                        <i class="fas fa-sliders-h"></i>
                        <div class="step-check"><i class="fas fa-check"></i></div>
                    </div>
                    <div class="step-content">
                        <div class="step-title">Difficulty</div>
                        <div class="step-subtitle">Set challenge level</div>
                    </div>
                </div>
                <div class="progress-step" data-step="4">
                    <div class="step-icon">
                        <i class="fas fa-question-circle"></i>
                        <div class="step-check"><i class="fas fa-check"></i></div>
                    </div>
                    <div class="step-content">
                        <div class="step-title">Quiz</div>
                        <div class="step-subtitle">Take the test</div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
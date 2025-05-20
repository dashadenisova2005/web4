<?php
// Начало сессии
session_start();
// Подключение к базе данных
require 'db.php';

// Инициализация массивов для сообщений, ошибок и значений полей
$messages = [];
$errors = [];
$values = [];

// Обработка GET-запроса
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Проверка наличия куки 'save' (успешное сохранение данных)
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', time() - 3600); // Удаляем куку
        $messages[] = 'Спасибо, результаты сохранены.';

        // Если есть сохраненные логин и пароль в куках, показываем их
        if (!empty($_COOKIE['login']) && !empty($_COOKIE['pass'])) {
            $messages[] = sprintf(
                'Вы можете <a href="login.php">войти</a> с логином <strong>%s</strong> и паролем <strong>%s</strong> для изменения данных.',
                htmlspecialchars($_COOKIE['login']),
                htmlspecialchars($_COOKIE['pass'])
            );
        }
    }

    // Список полей формы
    $field_names = ['name', 'phone', 'email', 'birthdate', 'gender', 'languages', 'bio', 'agreement'];
    
    // Заполнение массивов ошибок и значений из кук
    foreach ($field_names as $field) {
        $errors[$field] = !empty($_COOKIE[$field.'_error']) ? $_COOKIE[$field.'_error'] : '';
        if (!empty($errors[$field])) {
            setcookie($field.'_error', '', time() - 3600); // Удаляем куку с ошибкой
        }
        $values[$field] = empty($_COOKIE[$field.'_value']) ? '' : $_COOKIE[$field.'_value'];
    }

    // Если пользователь авторизован, загружаем его данные из БД
    if (!empty($_SESSION['login'])) {
        try {
            $stmt = $pdo->prepare("SELECT a.*, GROUP_CONCAT(l.name) as languages
                FROM applications a
                LEFT JOIN application_languages al ON a.id = al.application_id
                LEFT JOIN languages l ON al.language_id = l.id
                WHERE a.login = ?
                GROUP BY a.id");
            $stmt->execute([$_SESSION['login']]);
            $user_data = $stmt->fetch();

            if ($user_data) {
                $values = array_merge($values, $user_data);
                $values['languages'] = $user_data['languages'] ? explode(',', $user_data['languages']) : [];
            }
        } catch (PDOException $e) {
            $messages[] = '<div class="alert alert-danger">Ошибка загрузки данных: '.htmlspecialchars($e->getMessage()).'</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru-RU">
<head>
    <meta charset="UTF-8">
    <title>Форма заявки</title>
    <!-- Подключение Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Подключение иконок Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .form-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        .form-title {
            color: #0d6efd;
            margin-bottom: 25px;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 15px;
            display: block;
        }
        .btn-custom {
            margin-top: 20px;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-container">
                    <h2 class="form-title text-center"><i class="bi bi-person-fill"></i> Форма заявки</h2>
                    
                    <!-- Вывод сообщений -->
                    <?php if (!empty($messages)): ?>
                        <div class="mb-4">
                            <?php foreach ($messages as $message): ?>
                                <div class="alert alert-info d-flex align-items-center">
                                    <i class="bi bi-info-circle-fill me-2"></i>
                                    <?= $message ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Проверка наличия ошибок -->
                    <?php
                    $has_errors = false;
                    foreach ($errors as $error) {
                        if (!empty($error)) {
                            $has_errors = true;
                            break;
                        }
                    }
                    ?>

                    <!-- Вывод ошибок -->
                    <?php if ($has_errors): ?>
                        <div class="alert alert-danger mb-4">
                            <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Обнаружены ошибки:</h4>
                            <ul class="mb-0">
                                <?php foreach ($errors as $field => $error): ?>
                                    <?php if (!empty($error)): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Основная форма -->
                    <form action="a.php" method="POST" id="form">
                        <!-- Поле ФИО -->
                        <div class="mb-3">
                            <label class="form-label">
                                1) <i class="bi bi-person"></i> ФИО:
                            </label>
                            <input type="text" class="form-control <?php echo !empty($errors['name']) ? 'is-invalid' : ''; ?>" 
                                   placeholder="Введите ваше ФИО" name="name" id="name" required
                                   value="<?php echo htmlspecialchars($values['name'] ?? ''); ?>">
                            <?php if (!empty($errors['name'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['name']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Поле Телефон -->
                        <div class="mb-3">
                            <label class="form-label">
                                2) <i class="bi bi-telephone"></i> Телефон:
                            </label>
                            <input class="form-control <?php echo !empty($errors['phone']) ? 'is-invalid' : ''; ?>" 
                                   type="tel" placeholder="+123456-78-90" name="phone" id="phone" required
                                   value="<?php echo htmlspecialchars($values['phone'] ?? ''); ?>">
                            <?php if (!empty($errors['phone'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['phone']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Поле Email -->
                        <div class="mb-3">
                            <label class="form-label">
                                3) <i class="bi bi-envelope"></i> Email:
                            </label>
                            <input class="form-control <?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>" 
                                   type="email" placeholder="Введите вашу почту" name="email" id="email" required
                                   value="<?php echo htmlspecialchars($values['email'] ?? ''); ?>">
                            <?php if (!empty($errors['email'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Поле Дата рождения -->
                        <div class="mb-3">
                            <label class="form-label">
                                4) <i class="bi bi-calendar"></i> Дата рождения:
                            </label>
                            <input class="form-control <?php echo !empty($errors['birthdate']) ? 'is-invalid' : ''; ?>" 
                                   value="2000-07-15" type="date" name="birthdate" id="birthdate" required
                                   value="<?php echo htmlspecialchars($values['birthdate'] ?? ''); ?>">
                            <?php if (!empty($errors['birthdate'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['birthdate']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Поле Пол -->
                        <div class="mb-3">
                            <label class="form-label">
                                5) <i class="bi bi-gender-ambiguous"></i> Пол:
                            </label>
                            <div class="form-check">
                                <input class="form-check-input <?php echo !empty($errors['gender']) ? 'is-invalid' : ''; ?>" 
                                       type="radio" name="gender" id="male" value="male" required
                                       <?php echo ($values['gender'] ?? '') === 'male' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="male">Мужской</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input <?php echo !empty($errors['gender']) ? 'is-invalid' : ''; ?>" 
                                       type="radio" name="gender" id="female" value="female"
                                       <?php echo ($values['gender'] ?? '') === 'female' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="female">Женский</label>
                            </div>
                            <?php if (!empty($errors['gender'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['gender']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Поле Языки программирования -->
                        <div class="mb-3">
                            <label class="form-label">
                                6) <i class="bi bi-code-slash"></i> Любимый язык программирования:
                            </label>
                            <select class="form-select <?php echo !empty($errors['languages']) ? 'is-invalid' : ''; ?>" 
                                    id="languages" name="languages[]" multiple required size="5">
                                <?php
                                $allLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala'];
                                $selectedLanguages = isset($values['languages']) ? (is_array($values['languages']) ? $values['languages'] : explode(',', $values['languages'])) : [];

                                foreach ($allLanguages as $lang): ?>
                                    <option value="<?php echo htmlspecialchars($lang); ?>"
                                        <?php echo in_array($lang, $selectedLanguages) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lang); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Для выбора нескольких вариантов удерживайте Ctrl (Windows) или Command (Mac)</small>
                            <?php if (!empty($errors['languages'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['languages']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Поле Биография -->
                        <div class="mb-3">
                            <label class="form-label">
                                7) <i class="bi bi-file-text"></i> Биография:
                            </label>
                            <textarea class="form-control <?php echo !empty($errors['bio']) ? 'is-invalid' : ''; ?>" 
                                      id="bio" name="bio" rows="3" required><?php
                                      echo htmlspecialchars($values['bio'] ?? ''); ?></textarea>
                            <?php if (!empty($errors['bio'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['bio']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Чекбокс соглашения -->
                        <div class="mb-4 form-check">
                            <input class="form-check-input <?php echo !empty($errors['agreement']) ? 'is-invalid' : ''; ?>" 
                                   type="checkbox" name="agreement" id="agreement" value="1" required
                                   <?php echo ($values['agreement'] ?? '') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="agreement">
                                8) <i class="bi bi-file-earmark-check"></i> С контрактом ознакомлен(а)
                            </label>
                            <?php if (!empty($errors['agreement'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['agreement']); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Кнопки отправки формы и выхода -->
                        <div class="d-flex justify-content-between">
                            <button type="submit" name="save" class="btn btn-primary btn-custom">
                                <i class="bi bi-send"></i> Опубликовать
                            </button>
                            
                            <?php if (!empty($_SESSION['login'])): ?>
                                <a href="logout.php" class="btn btn-danger btn-custom">
                                    <i class="bi bi-box-arrow-right"></i> Выйти
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Подключение Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
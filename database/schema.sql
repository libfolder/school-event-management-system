-- Database: p10_school_event_management
-- School Event Management System

CREATE DATABASE IF NOT EXISTS p10_school_event_management DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE p10_school_event_management;

-- Users: teachers and administrators
CREATE TABLE users (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username    VARCHAR(50)  NOT NULL,
    password    VARCHAR(255) NOT NULL,
    name        VARCHAR(100) NOT NULL,
    role        ENUM('admin','teacher') NOT NULL DEFAULT 'teacher',
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Classes: school classes (grades)
CREATE TABLE classes (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(50)  NOT NULL,
    grade       INT UNSIGNED NOT NULL,
    section     VARCHAR(10)  NOT NULL DEFAULT 'A',
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students
CREATE TABLE students (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id    VARCHAR(20)  NOT NULL,
    first_name    VARCHAR(60)  NOT NULL,
    last_name     VARCHAR(60)  NOT NULL,
    melli_code    VARCHAR(10)  NULL,
    father_name   VARCHAR(100) NULL,
    mother_name   VARCHAR(100) NULL,
    grade         VARCHAR(20)  NULL,
    mother_phone  VARCHAR(20)  NULL,
    father_phone  VARCHAR(20)  NULL,
    address       VARCHAR(255) NULL,
    photo         VARCHAR(255) NULL,
    class_id      INT UNSIGNED NOT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY student_id (student_id),
    UNIQUE KEY melli_code (melli_code),
    KEY class_id (class_id),
    CONSTRAINT fk_students_class FOREIGN KEY (class_id)
        REFERENCES classes(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event Types: templates for events with default scores
-- is_positive: 1 = positive, 0 = negative
CREATE TABLE event_types (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100) NOT NULL,
    description   TEXT         NULL,
    default_score INT          NOT NULL DEFAULT 0,
    is_positive   TINYINT(1)   NOT NULL DEFAULT 0,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events: individual event records for students
CREATE TABLE events (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id  INT UNSIGNED NOT NULL,
    event_type_id INT UNSIGNED NOT NULL,
    score       INT          NOT NULL,
    description TEXT,
    teacher_id  INT UNSIGNED NOT NULL,
    event_date  DATE         NOT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY student_id (student_id),
    KEY event_type_id (event_type_id),
    KEY teacher_id (teacher_id),
    CONSTRAINT fk_events_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_events_event_type FOREIGN KEY (event_type_id)
        REFERENCES event_types(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_events_teacher FOREIGN KEY (teacher_id)
        REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Actions: actions taken based on events or score thresholds
-- Action types:
--   Positive:  reward, honor_board, student_of_week, prize
--   Negative:  teacher_referral, parent_meeting, mentor_referral
CREATE TABLE actions (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id          INT UNSIGNED NULL,
    student_id        INT UNSIGNED NOT NULL,
    action_type       ENUM('reward','honor_board','student_of_week','prize','teacher_referral','parent_meeting','mentor_referral') NOT NULL,
    title             VARCHAR(255) NOT NULL,
    description       TEXT,
    is_automatic      TINYINT(1)   NOT NULL DEFAULT 0,
    score_threshold_min INT        NULL,
    score_threshold_max INT        NULL,
    status            ENUM('open','completed') NOT NULL DEFAULT 'open',
    result            TEXT,
    action_date       DATE         NULL,
    completed_date    DATE         NULL,
    created_by        INT UNSIGNED NOT NULL,
    created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY event_id (event_id),
    KEY student_id (student_id),
    KEY action_type (action_type),
    KEY status (status),
    CONSTRAINT fk_actions_event FOREIGN KEY (event_id)
        REFERENCES events(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_actions_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_actions_created_by FOREIGN KEY (created_by)
        REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Absences are recorded as events with the "غیبت روزانه" event type
-- (negative score), so no separate absences table is needed.

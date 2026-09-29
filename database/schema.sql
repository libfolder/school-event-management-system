-- Database: p10_school_event_management
-- School Event Management System

CREATE DATABASE IF NOT EXISTS p10_school_event_management DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE p10_school_event_management;

-- Users: teachers and administrators
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT, -- شناسه یکتا
    username    VARCHAR(50)  NOT NULL,                -- نام کاربری ورود
    password    VARCHAR(255) NOT NULL,                -- رمز عبور
    first_name  VARCHAR(60)  NOT NULL,                -- نام
    last_name   VARCHAR(60)  NOT NULL,                -- نام خانوادگی
    phone       VARCHAR(20)  NULL,                    -- شماره تماس
    email       VARCHAR(100) NULL,                    -- پست الکترونیکی
    role        ENUM('admin','teacher') NOT NULL DEFAULT 'teacher', -- نقش دسترسی
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP, -- زمان ایجاد
    updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- زمان آخرین ویرایش
    PRIMARY KEY (id),
    UNIQUE KEY username (username),
    UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Classes: school classes (grades)
CREATE TABLE IF NOT EXISTS classes (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT, -- شناسه یکتا کلاس
    name        VARCHAR(50)  NOT NULL,                -- نام کلاس
    grade       INT UNSIGNED NOT NULL,                -- پایه تحصیلی
    teacher_name VARCHAR(100) NULL,                   -- نام معلم کلاس
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP, -- زمان ایجاد
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students
CREATE TABLE IF NOT EXISTS students (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT, -- شناسه یکتا
    student_id    VARCHAR(20)  NOT NULL,                -- شماره دانش‌آموزی
    first_name    VARCHAR(60)  NOT NULL,                -- نام
    last_name     VARCHAR(60)  NOT NULL,                -- نام خانوادگی
    melli_code    VARCHAR(10)  NULL,                    -- کد ملی
    father_name   VARCHAR(100) NULL,                    -- نام پدر
    mother_name   VARCHAR(100) NULL,                    -- نام مادر
    grade         VARCHAR(20)  NULL,                    -- پایه تحصیلی
    mother_phone  VARCHAR(20)  NULL,                    -- شماره تماس مادر
    father_phone  VARCHAR(20)  NULL,                    -- شماره تماس پدر
    address       VARCHAR(255) NULL,                    -- آدرس
    photo         VARCHAR(255) NULL,                    -- عکس پرسنلی
    class_id      INT UNSIGNED NOT NULL,                -- کلاس مرتبط
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP, -- زمان ثبت
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
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT, -- شناسه یکتا
    name          VARCHAR(100) NOT NULL,                -- نام نوع رویداد
    description   TEXT         NULL,                    -- توضیحات
    default_score INT          NOT NULL DEFAULT 0,       -- امتیاز پیش‌فرض
    is_positive   TINYINT(1)   NOT NULL DEFAULT 0,      -- مثبت یا منفی
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP, -- زمان ایجاد
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events: individual event records for students
CREATE TABLE events (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT, -- شناسه یکتا رویداد
    student_id  INT UNSIGNED NOT NULL,                -- دانش‌آموز مرتبط
    event_type_id INT UNSIGNED NOT NULL,              -- نوع رویداد
    score       INT          NOT NULL,                 -- امتیاز رویداد
    description TEXT,                                  -- توضیحات
    teacher_id  INT UNSIGNED NOT NULL,                 -- معلم ثبت‌کننده
    event_date  DATE         NOT NULL,                 -- تاریخ رویداد
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP, -- زمان ثبت
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
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT, -- شناسه یکتا اقدام
    event_id          INT UNSIGNED NULL,                    -- رویداد مرتبط
    student_id        INT UNSIGNED NOT NULL,                -- دانش‌آموز
    action_type       ENUM('reward','honor_board','student_of_week','prize','teacher_referral','parent_meeting','mentor_referral') NOT NULL, -- نوع اقدام
    title             VARCHAR(255) NOT NULL,                 -- عنوان
    description       TEXT,                                  -- توضیحات
    is_automatic      TINYINT(1)   NOT NULL DEFAULT 0,       -- ثبت خودکار
    score_threshold_min INT        NULL,                     -- حداقل امتیاز
    score_threshold_max INT        NULL,                     -- حداکثر امتیاز
    status            ENUM('open','completed') NOT NULL DEFAULT 'open', -- وضعیت
    result            TEXT,                                  -- نتیجه
    action_date       DATE         NULL,                     -- تاریخ اقدام
    completed_date    DATE         NULL,                     -- تاریخ تکمیل
    created_by        INT UNSIGNED NOT NULL,                 -- ثبت‌کننده
    created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP, -- زمان ثبت
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

-- Attendance: daily student attendance records
CREATE TABLE attendance (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT, -- شناسه یکتا
    student_id      INT UNSIGNED NOT NULL,                -- دانش‌آموز
    attendance_date DATE         NOT NULL,                 -- تاریخ حضور
    status          ENUM('present','absent','late','excused') NOT NULL DEFAULT 'present', -- وضعیت
    period          VARCHAR(50)  NULL,                    -- بازه زمانی
    notes           TEXT         NULL,                    -- توضیحات
    recorded_by     INT UNSIGNED NOT NULL,                 -- ثبت‌کننده
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP, -- زمان ایجاد
    updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- زمان ویرایش
    PRIMARY KEY (id),
    KEY student_id (student_id),
    KEY attendance_date (attendance_date),
    KEY recorded_by (recorded_by),
    KEY idx_attendance_student_date (student_id, attendance_date),
    CONSTRAINT fk_attendance_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_attendance_recorded_by FOREIGN KEY (recorded_by)
        REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

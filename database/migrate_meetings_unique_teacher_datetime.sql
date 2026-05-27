ALTER TABLE meetings
ADD CONSTRAINT uniq_meetings_teacher_datetime UNIQUE (teacher_id, meeting_date, meeting_time);

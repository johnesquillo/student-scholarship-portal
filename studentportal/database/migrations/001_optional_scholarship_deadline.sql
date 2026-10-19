-- Keep existing dates; NULL means no deadline has been announced.
ALTER TABLE scholarships MODIFY COLUMN deadline DATE NULL DEFAULT NULL;

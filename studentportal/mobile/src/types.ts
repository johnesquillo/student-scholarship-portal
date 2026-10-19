export type User = {
  id: number;
  name: string;
  email: string;
  role: "user" | "student" | "admin" | "staff";
};
export type Scholarship = {
  scholarship_id: number;
  name: string;
  description: string;
  deadline: string | null;
};
export type Application = {
  user_id: number;
  scholarship_id: number;
  scholarship_name: string;
  name: string;
  email: string;
  course: string;
  gwa: string;
  year_level: string;
  application_date: string;
  status: "Pending" | "Approved" | "Rejected";
  uploaded_files: string;
};
export type Message = {
  id: number;
  name: string;
  email: string;
  message: string;
  created_at: string;
};
export type Requirement = { index: number; name: string; available: boolean };
export type Page =
  | "home"
  | "scholarships"
  | "apply"
  | "applications"
  | "faq"
  | "contact"
  | "admin"
  | "inbox"
  | "requirements";
export type Route = { page: Page; scholarshipId?: number; userId?: number };

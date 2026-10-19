import React, { useCallback, useEffect, useRef, useState } from "react";
import {
  Linking,
  Platform,
  Pressable,
  View,
  useWindowDimensions,
} from "react-native";
import * as DocumentPicker from "expo-document-picker";
import { api, documentUrl } from "./api";
import {
  Badge,
  Button,
  Card,
  Confirm,
  Empty,
  Field,
  Icon,
  IconName,
  Loader,
  Notice,
  Select,
  styles,
  Txt,
} from "./components";
import Documents, { saveDocument } from "./Documents";
import { faqs, office } from "./data";
import { useTheme } from "./theme";
import {
  Application,
  Message,
  Requirement,
  Route,
  Scholarship,
  User,
} from "./types";

export type ScreenProps = {
  user: User;
  navigate: (route: Route) => void;
  notify: (message: string, error?: boolean) => void;
};
function useResource<T>(action: string, query: Record<string, number> = {}) {
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const sequence = useRef(0);
  const mounted = useRef(false);
  const key = JSON.stringify(query);
  const reload = useCallback(async () => {
    const current = ++sequence.current;
    setLoading(true);
    setError("");
    try {
      const response = await api<T>(action, undefined, JSON.parse(key));
      if (mounted.current && current === sequence.current) setData(response);
    } catch (error) {
      if (mounted.current && current === sequence.current)
        setError((error as Error).message);
    } finally {
      if (mounted.current && current === sequence.current) setLoading(false);
    }
  }, [action, key]);
  useEffect(() => {
    mounted.current = true;
    const current = ++sequence.current;
    api<T>(action, undefined, JSON.parse(key))
      .then((response) => {
        if (mounted.current && current === sequence.current) setData(response);
      })
      .catch((error) => {
        if (mounted.current && current === sequence.current)
          setError((error as Error).message);
      })
      .finally(() => {
        if (mounted.current && current === sequence.current) setLoading(false);
      });
    return () => {
      mounted.current = false;
    };
  }, [action, key]);
  return { data, loading, error, reload };
}
function ResourceError({
  message,
  retry,
}: {
  message: string;
  retry: () => void;
}) {
  return (
    <Card>
      <Notice text={message} error />
      <Button
        title="Try again"
        icon="refresh-cw"
        variant="secondary"
        onPress={retry}
      />
    </Card>
  );
}
export const deadline = (value: string | null) =>
  value
    ? new Date(value + "T00:00:00").toLocaleDateString("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
      })
    : "No deadline announced";
const programIcon = (name: string): IconName =>
  name.startsWith("CARRI")
    ? "music"
    : name.startsWith("BOARD")
      ? "book-open"
      : name.startsWith("CREATE")
        ? "feather"
        : "award";
export function PageHeading({
  eyebrow,
  title,
  description,
  children,
}: React.PropsWithChildren<{
  eyebrow: string;
  title: string;
  description: string;
}>) {
  const { colors } = useTheme();
  return (
    <View style={{ gap: 6, marginBottom: 8 }}>
      <Txt size={11} bold style={{ letterSpacing: 2, color: colors.primary }}>
        {eyebrow.toUpperCase()}
      </Txt>
      <View style={[styles.row, { justifyContent: "space-between" }]}>
        <Txt size={30} bold style={{ letterSpacing: -0.8 }}>
          {title}
        </Txt>
        {children}
      </View>
      <Txt muted>{description}</Txt>
    </View>
  );
}
function Stat({
  title,
  count,
  icon,
  status,
}: {
  title: string;
  count: number;
  icon: IconName;
  status?: string;
}) {
  const { colors } = useTheme();
  return (
    <Card style={{ flex: 1, minWidth: 140, padding: 20 }}>
      <View style={[styles.row, { justifyContent: "space-between" }]}>
        <Icon name={icon} color={colors.primary} />
        <Txt muted size={12}>
          {status || "APPLICATIONS"}
        </Txt>
      </View>
      <Txt bold size={34} style={{ lineHeight: 40 }}>
        {count.toString().padStart(2, "0")}
      </Txt>
      <Txt size={13} muted>
        {title}
      </Txt>
    </Card>
  );
}
function ProgramCard({
  item,
  onApply,
  compact = false,
}: {
  item: Scholarship;
  onApply: () => void;
  compact?: boolean;
}) {
  const { colors } = useTheme();
  return (
    <Card
      style={{ flex: 1, minWidth: 250, maxWidth: compact ? undefined : 580 }}
    >
      <View style={[styles.row, { justifyContent: "space-between" }]}>
        <View
          style={{
            padding: 13,
            borderRadius: 15,
            backgroundColor: colors.soft,
          }}
        >
          <Icon
            name={programIcon(item.name)}
            color={colors.primary}
            size={24}
          />
        </View>
        <View
          style={{
            borderRadius: 20,
            paddingHorizontal: 10,
            paddingVertical: 5,
            backgroundColor: colors.successBg,
          }}
        >
          <Txt size={11} bold style={{ color: colors.primary }}>
            OPEN FOR APPLICATIONS
          </Txt>
        </View>
      </View>
      <Txt size={22} bold>
        {item.name.split("(")[0].trim()}
      </Txt>
      <Txt muted size={14} style={{ minHeight: compact ? 0 : 64 }}>
        {item.description}
      </Txt>
      <View style={[styles.row, { marginTop: "auto" }]}>
        <Icon name="calendar" size={14} color={colors.muted} />
        <Txt size={12} muted>
          {deadline(item.deadline)}
        </Txt>
      </View>
      <Button
        title="Apply now"
        icon="arrow-up-right"
        onPress={onApply}
        style={{ width: "100%" }}
      />
    </Card>
  );
}
export function Dashboard({ user, navigate }: ScreenProps) {
  const admin = ["admin", "staff"].includes(user.role);
  const { colors } = useTheme();
  const { width } = useWindowDimensions();
  const apps = useResource<{ applications: Application[] }>("applications");
  const programs = useResource<{ scholarships: Scholarship[] }>("scholarships");
  if (apps.loading && !apps.data) return <Loader />;
  if (apps.error)
    return <ResourceError message={apps.error} retry={apps.reload} />;
  const applications = apps.data?.applications || [];
  return (
    <View style={styles.stack}>
      <PageHeading
        eyebrow={
          admin ? "Scholarship administration" : "Your scholarship journey"
        }
        title={`Welcome back, ${user.name.split(" ")[0]}.`}
        description={
          admin
            ? "Everything you need to review and support your applicants."
            : "Discover opportunities, follow your progress, and take your next step."
        }
      />
      <View
        style={{
          backgroundColor: colors.sidebar,
          borderRadius: 24,
          padding: width < 600 ? 24 : 36,
          gap: 18,
          overflow: "hidden",
        }}
      >
        <View
          style={{
            position: "absolute",
            right: -40,
            top: -80,
            width: 300,
            height: 300,
            borderRadius: 150,
            borderWidth: 50,
            borderColor: "#FFFFFF06",
          }}
        />
        <Txt size={11} bold style={{ color: "#E2C184", letterSpacing: 2 }}>
          PAMANTASAN NG LUNGSOD NG MUNTINLUPA
        </Txt>
        <Txt
          size={width < 600 ? 28 : 36}
          bold
          style={{ color: "#F5F7EF", maxWidth: 620, letterSpacing: -0.6 }}
        >
          {admin
            ? "A clearer view of every application."
            : "Your education. Your next opportunity."}
        </Txt>
        <Txt style={{ color: "#C4D9CB", maxWidth: 520 }}>
          {admin
            ? "Review submitted requirements and help students move forward."
            : "Explore scholarships created to support your talent, leadership, and academic journey."}
        </Txt>
        <Button
          title={admin ? "Review applications" : "Explore scholarships"}
          variant="accent"
          icon="arrow-right"
          onPress={() => navigate({ page: admin ? "admin" : "scholarships" })}
        />
      </View>
      <View style={styles.grid}>
        <Stat
          title="Pending review"
          count={applications.filter((a) => a.status === "Pending").length}
          icon="clock"
        />
        <Stat
          title="Approved"
          count={applications.filter((a) => a.status === "Approved").length}
          icon="check-circle"
        />
        <Stat
          title="Rejected"
          count={applications.filter((a) => a.status === "Rejected").length}
          icon="x-circle"
        />
      </View>
      <View style={[styles.row, { justifyContent: "space-between" }]}>
        <Txt size={22} bold>
          {admin ? "Latest applications" : "Find your opportunity"}
        </Txt>
        <Button
          title="View all"
          variant="ghost"
          icon="arrow-right"
          compact
          onPress={() => navigate({ page: admin ? "admin" : "scholarships" })}
        />
      </View>
      {admin ? (
        applications.length ? (
          applications.slice(0, 4).map((item) => (
            <Card
              key={`${item.user_id}-${item.scholarship_id}`}
              style={{ padding: 20 }}
            >
              <View style={styles.row}>
                <View style={{ flex: 1, minWidth: 160 }}>
                  <Txt bold>{item.name}</Txt>
                  <Txt muted size={13}>
                    {item.scholarship_name}
                  </Txt>
                </View>
                <Badge status={item.status} />
                <Button
                  title="Requirements"
                  variant="secondary"
                  icon="folder"
                  compact
                  onPress={() =>
                    navigate({
                      page: "requirements",
                      userId: Number(item.user_id),
                      scholarshipId: Number(item.scholarship_id),
                    })
                  }
                />
              </View>
            </Card>
          ))
        ) : (
          <Empty
            title="A fresh start"
            description="Student submissions will appear here when applicants apply."
          />
        )
      ) : programs.error ? (
        <ResourceError message={programs.error} retry={programs.reload} />
      ) : (
        <View style={styles.grid}>
          {(programs.data?.scholarships || []).slice(0, 4).map((item) => (
            <ProgramCard
              key={item.scholarship_id}
              item={item}
              compact
              onApply={() =>
                navigate({
                  page: "apply",
                  scholarshipId: Number(item.scholarship_id),
                })
              }
            />
          ))}
          {!programs.loading && !programs.data?.scholarships.length && (
            <Empty
              title="More opportunities ahead"
              description="New scholarship openings will appear here when they become available."
              icon="award"
            />
          )}
        </View>
      )}
      {!admin && (
        <Card>
          <View style={styles.row}>
            <Icon name="help-circle" size={28} color={colors.primary} />
            <View style={{ flex: 1, minWidth: 180 }}>
              <Txt bold size={18}>
                A little guidance goes a long way.
              </Txt>
              <Txt muted size={14}>
                Find answers to common questions or reach the scholarship
                office.
              </Txt>
            </View>
            <Button
              title="Read the FAQs"
              variant="secondary"
              icon="arrow-right"
              onPress={() => navigate({ page: "faq" })}
            />
          </View>
        </Card>
      )}
    </View>
  );
}
export function Scholarships({ navigate }: ScreenProps) {
  const { data, loading, error, reload } = useResource<{
    scholarships: Scholarship[];
  }>("scholarships");
  const [search, setSearch] = useState("");
  const items = (data?.scholarships || []).filter((item) =>
    `${item.name} ${item.description}`
      .toLowerCase()
      .includes(search.toLowerCase()),
  );
  return (
    <View style={styles.stack}>
      <PageHeading
        eyebrow="Explore opportunities"
        title="Available scholarships"
        description="Choose the program that fits your path. Your next chapter starts here."
      />
      <Field
        label="Search scholarships"
        placeholder="Search by program or keyword…"
        value={search}
        onChangeText={setSearch}
      />
      {loading ? (
        <Loader />
      ) : error ? (
        <ResourceError message={error} retry={reload} />
      ) : items.length ? (
        <View style={styles.grid}>
          {items.map((item) => (
            <ProgramCard
              key={item.scholarship_id}
              item={item}
              onApply={() =>
                navigate({
                  page: "apply",
                  scholarshipId: Number(item.scholarship_id),
                })
              }
            />
          ))}
        </View>
      ) : (
        <Empty
          title="No scholarships found"
          description={
            search
              ? "Try another keyword to find the right opportunity."
              : "Please check back for upcoming scholarship openings."
          }
          icon="search"
        />
      )}
    </View>
  );
}
export function Applications({ user, navigate, notify }: ScreenProps) {
  const admin = ["admin", "staff"].includes(user.role);
  const { data, loading, error, reload } = useResource<{
    applications: Application[];
  }>("applications");
  const [filter, setFilter] = useState("All");
  const [search, setSearch] = useState("");
  const [busy, setBusy] = useState(false);
  const [confirm, setConfirm] = useState<{
    item: Application;
    operation: string;
  } | null>(null);
  const { colors } = useTheme();
  const items = (data?.applications || []).filter(
    (item) =>
      (filter === "All" || item.status === filter) &&
      `${item.name} ${item.email} ${item.course} ${item.scholarship_name}`
        .toLowerCase()
        .includes(search.toLowerCase()),
  );
  const act = async () => {
    if (!confirm) return;
    const selected = confirm;
    setConfirm(null);
    setBusy(true);
    try {
      await api("application_action", {
        user_id: selected.item.user_id,
        scholarship_id: selected.item.scholarship_id,
        operation: selected.operation,
      });
      notify(
        selected.operation === "remove"
          ? "Application removed."
          : selected.operation === "reapply"
            ? "Application returned to pending review."
            : `Application ${selected.operation.toLowerCase()}.`,
      );
      await reload();
    } catch (error) {
      notify((error as Error).message, true);
    } finally {
      setBusy(false);
    }
  };
  return (
    <View style={styles.stack}>
      <PageHeading
        eyebrow={admin ? "Review & decisions" : "Your progress"}
        title={admin ? "Manage applications" : "My applications"}
        description={
          admin
            ? "Review each applicant’s details and requirements before making a decision."
            : "Track every submission and stay up to date on your application status."
        }
      >
        <Button
          title="Refresh"
          icon="refresh-cw"
          variant="secondary"
          compact
          onPress={reload}
          loading={loading && !!data}
        />
      </PageHeading>
      <View style={styles.row}>
        {["All", "Pending", "Approved", "Rejected"].map((status) => (
          <Button
            key={status}
            title={`${status} (${(data?.applications || []).filter((item) => status === "All" || item.status === status).length})`}
            variant={filter === status ? "primary" : "secondary"}
            compact
            onPress={() => setFilter(status)}
          />
        ))}
      </View>
      <Field
        label={admin ? "Search applicants" : "Search applications"}
        value={search}
        onChangeText={setSearch}
        placeholder={
          admin
            ? "Name, email, course, or scholarship…"
            : "Search your scholarships…"
        }
      />
      {loading && !data ? (
        <Loader />
      ) : error ? (
        <ResourceError message={error} retry={reload} />
      ) : !items.length ? (
        <Empty
          title={
            search || filter !== "All"
              ? "No matching applications"
              : "No applications yet"
          }
          description={
            admin
              ? "New student submissions will appear here."
              : "Find an available scholarship and submit your first application."
          }
          icon="file-text"
        >
          {!admin && (
            <Button
              title="Browse scholarships"
              icon="arrow-right"
              onPress={() => navigate({ page: "scholarships" })}
            />
          )}
        </Empty>
      ) : (
        items.map((item) => (
          <Card key={`${item.user_id}-${item.scholarship_id}`}>
            <View style={styles.row}>
              <View style={{ flex: 1, minWidth: 180 }}>
                <Txt size={19} bold>
                  {admin ? item.name : item.scholarship_name}
                </Txt>
                <Txt size={13} muted>
                  {admin ? item.scholarship_name : item.name}
                </Txt>
              </View>
              <Badge status={item.status} />
            </View>
            <View style={[styles.row, { gap: 26 }]}>
              <View>
                <Txt muted size={11}>
                  COURSE / YEAR
                </Txt>
                <Txt size={14}>
                  {item.course} · {item.year_level}
                </Txt>
              </View>
              <View>
                <Txt muted size={11}>
                  GWA
                </Txt>
                <Txt size={14}>{String(item.gwa)}</Txt>
              </View>
              <View>
                <Txt muted size={11}>
                  SUBMITTED
                </Txt>
                <Txt size={14}>{item.application_date?.split(" ")[0]}</Txt>
              </View>
              {admin && (
                <View>
                  <Txt muted size={11}>
                    EMAIL
                  </Txt>
                  <Txt size={14}>{item.email}</Txt>
                </View>
              )}
            </View>
            {admin && (
              <View
                style={[
                  styles.row,
                  {
                    paddingTop: 16,
                    borderTopWidth: 1,
                    borderTopColor: colors.border,
                  },
                ]}
              >
                <Button
                  title="View requirements"
                  icon="folder"
                  variant="secondary"
                  compact
                  onPress={() =>
                    navigate({
                      page: "requirements",
                      userId: Number(item.user_id),
                      scholarshipId: Number(item.scholarship_id),
                    })
                  }
                />
                {item.status === "Pending" && (
                  <>
                    <Button
                      title="Approve"
                      icon="check"
                      compact
                      disabled={busy}
                      onPress={() =>
                        setConfirm({ item, operation: "Approved" })
                      }
                    />
                    <Button
                      title="Reject"
                      icon="x"
                      variant="danger"
                      compact
                      disabled={busy}
                      onPress={() =>
                        setConfirm({ item, operation: "Rejected" })
                      }
                    />
                  </>
                )}
                {item.status === "Rejected" && (
                  <Button
                    title="Return to pending"
                    icon="rotate-ccw"
                    variant="secondary"
                    compact
                    disabled={busy}
                    onPress={() => setConfirm({ item, operation: "reapply" })}
                  />
                )}
                <Button
                  title="Remove"
                  icon="trash-2"
                  variant="ghost"
                  compact
                  disabled={busy}
                  onPress={() => setConfirm({ item, operation: "remove" })}
                />
              </View>
            )}
          </Card>
        ))
      )}
      {confirm && (
        <Confirm
          title={
            confirm.operation === "remove"
              ? "Remove this application?"
              : "Confirm application decision"
          }
          description={`${confirm.item.name} · ${confirm.item.scholarship_name}. ${confirm.operation === "remove" ? "The application record will be removed." : confirm.operation === "reapply" ? "This application will return to pending review." : `The application will be marked ${confirm.operation.toLowerCase()}.`}`}
          onConfirm={act}
          onCancel={() => setConfirm(null)}
        />
      )}
    </View>
  );
}
export function Apply({
  user,
  navigate,
  notify,
  scholarshipId,
}: ScreenProps & { scholarshipId?: number }) {
  const { data, loading, error, reload } = useResource<{
    scholarships: Scholarship[];
  }>("scholarships");
  const [values, setValues] = useState({
    scholarship_id: scholarshipId ? String(scholarshipId) : "",
    name: user.name,
    email: user.email,
    gwa: "",
    course: "",
    year: "",
  });
  const [files, setFiles] = useState<DocumentPicker.DocumentPickerAsset[]>([]);
  const [busy, setBusy] = useState(false);
  const [failure, setFailure] = useState("");
  const { colors } = useTheme();
  const update = (key: keyof typeof values, value: string) =>
    setValues((old) => ({ ...old, [key]: value }));
  const chooseFiles = async () => {
    try {
      const result = await DocumentPicker.getDocumentAsync({
        multiple: true,
        type: ["application/pdf", "image/jpeg"],
        copyToCacheDirectory: true,
      });
      if (result.canceled) return;
      const next = [...files, ...result.assets];
      if (next.length > 10) throw new Error("Choose up to 10 documents.");
      if (next.some((file) => (file.size || 0) > 5 * 1024 * 1024))
        throw new Error("Each document must be 5 MB or smaller.");
      setFiles(next);
      setFailure("");
    } catch (error) {
      setFailure((error as Error).message);
    }
  };
  const submit = async () => {
    if (Object.values(values).some((value) => !value.trim()) || !files.length) {
      setFailure("Complete every field and attach at least one requirement.");
      return;
    }
    setBusy(true);
    setFailure("");
    try {
      const body = new FormData();
      Object.entries(values).forEach(([key, value]) => body.append(key, value));
      for (const file of files) {
        if (Platform.OS === "web")
          body.append(
            "documents[]",
            file.file || (await (await fetch(file.uri)).blob()),
            file.name,
          );
        else
          body.append("documents[]", {
            uri: file.uri,
            name: file.name,
            type:
              file.mimeType ||
              (file.name.toLowerCase().endsWith(".pdf")
                ? "application/pdf"
                : "image/jpeg"),
          } as any);
      }
      await api("apply", body);
      notify("Your application has been submitted successfully.");
      navigate({ page: "applications" });
    } catch (error) {
      setFailure((error as Error).message);
    } finally {
      setBusy(false);
    }
  };
  if (loading) return <Loader />;
  if (error) return <ResourceError message={error} retry={reload} />;
  return (
    <View style={styles.stack}>
      <PageHeading
        eyebrow="Take the next step"
        title="Scholarship application"
        description="Tell us about yourself and attach your supporting requirements."
      />
      <Button
        title="Back to scholarships"
        icon="arrow-left"
        variant="ghost"
        compact
        onPress={() => navigate({ page: "scholarships" })}
      />
      {failure && <Notice error text={failure} />}
      {!data?.scholarships.length ? (
        <Empty
          title="Applications are currently closed"
          description="Please check back for upcoming opportunities."
          icon="calendar"
        />
      ) : (
        <>
          <Card>
            <Txt size={20} bold>
              01 · Choose your scholarship
            </Txt>
            <Select
              label="Scholarship program"
              value={values.scholarship_id}
              onChange={(value) => update("scholarship_id", value)}
              options={data.scholarships.map((item) => ({
                value: String(item.scholarship_id),
                label: item.name,
              }))}
            />
            <Txt muted size={13}>
              {deadline(
                data.scholarships.find(
                  (item) =>
                    String(item.scholarship_id) === values.scholarship_id,
                )?.deadline || null,
              )}
            </Txt>
          </Card>
          <Card>
            <Txt size={20} bold>
              02 · Personal & academic information
            </Txt>
            <Field
              label="Full name"
              value={values.name}
              onChangeText={(value) => update("name", value)}
              maxLength={100}
            />
            <Field
              label="Email address"
              value={values.email}
              onChangeText={(value) => update("email", value)}
              keyboardType="email-address"
              autoCapitalize="none"
            />
            <View style={styles.grid}>
              <View style={{ flex: 1, minWidth: 180 }}>
                <Field
                  label="Course"
                  placeholder="e.g. BS Computer Science"
                  value={values.course}
                  onChangeText={(value) => update("course", value)}
                  maxLength={150}
                />
              </View>
              <View style={{ flex: 1, minWidth: 120 }}>
                <Field
                  label="GWA"
                  placeholder="e.g. 1.75"
                  value={values.gwa}
                  onChangeText={(value) => update("gwa", value)}
                  keyboardType="decimal-pad"
                />
              </View>
            </View>
            <Select
              label="Year level"
              value={values.year}
              onChange={(value) => update("year", value)}
              options={["1st Year", "2nd Year", "3rd Year", "4th Year"].map(
                (year) => ({ value: year, label: year }),
              )}
            />
          </Card>
          <Card>
            <Txt size={20} bold>
              03 · Supporting requirements
            </Txt>
            <Txt muted>Attach 1–10 PDF or JPG files, up to 5 MB each.</Txt>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Choose requirement files"
              onPress={chooseFiles}
              style={{
                padding: 30,
                alignItems: "center",
                borderWidth: 1,
                borderStyle: "dashed",
                borderColor: colors.primary,
                borderRadius: 16,
                gap: 10,
                backgroundColor: colors.soft,
              }}
            >
              <Icon name="upload-cloud" size={30} color={colors.primary} />
              <Txt bold>Choose your files</Txt>
              <Txt muted size={12}>
                PDF / JPG · Maximum 5 MB per file
              </Txt>
            </Pressable>
            {files.map((file, index) => (
              <View style={styles.row} key={`${index}-${file.name}`}>
                <Icon name="file-text" color={colors.primary} />
                <View style={{ flex: 1 }}>
                  <Txt size={14}>{file.name}</Txt>
                  <Txt muted size={12}>
                    {file.size
                      ? `${(file.size / 1024).toFixed(0)} KB`
                      : "Selected document"}
                  </Txt>
                </View>
                <Button
                  title="Remove file"
                  icon="x"
                  compact
                  variant="ghost"
                  onPress={() =>
                    setFiles((old) => old.filter((_, i) => i !== index))
                  }
                />
              </View>
            ))}
            <Button
              title="Submit application"
              icon="arrow-right"
              loading={busy}
              onPress={submit}
              style={{ width: "100%", marginTop: 8 }}
            />
          </Card>
        </>
      )}
    </View>
  );
}
export function Requirements({
  userId,
  scholarshipId,
  navigate,
  notify,
}: ScreenProps & { userId: number; scholarshipId: number }) {
  const { data, loading, error, reload } = useResource<{
    application: Application;
    files: Requirement[];
  }>("requirements", { user_id: userId, scholarship_id: scholarshipId });
  const [preview, setPreview] = useState<Requirement | null>(null);
  const [busy, setBusy] = useState<number | null>(null);
  const download = async (file: Requirement) => {
    setBusy(file.index);
    try {
      await saveDocument(
        documentUrl(userId, scholarshipId, file.index),
        file.name,
      );
    } catch (error) {
      notify((error as Error).message, true);
    } finally {
      setBusy(null);
    }
  };
  if (loading) return <Loader />;
  if (error) return <ResourceError message={error} retry={reload} />;
  if (!data) return null;
  const item = data.application;
  return (
    <View style={styles.stack}>
      <PageHeading
        eyebrow="Applicant review"
        title="Submitted requirements"
        description="Review the applicant’s information and supporting documents."
      />
      <Button
        title="Back to applications"
        variant="ghost"
        icon="arrow-left"
        compact
        onPress={() => navigate({ page: "admin" })}
      />
      <Card>
        <View style={styles.row}>
          <View style={{ flex: 1 }}>
            <Txt size={24} bold>
              {item.name}
            </Txt>
            <Txt muted>{item.scholarship_name}</Txt>
          </View>
          <Badge status={item.status} />
        </View>
        <View style={styles.grid}>
          {[
            ["Email", item.email],
            ["Course / Year", `${item.course} / ${item.year_level}`],
            ["GWA", item.gwa],
            ["Application date", item.application_date],
          ].map(([label, value]) => (
            <View style={{ minWidth: 180, flex: 1, gap: 5 }} key={label}>
              <Txt muted size={12}>
                {label}
              </Txt>
              <Txt size={14}>{String(value)}</Txt>
            </View>
          ))}
        </View>
      </Card>
      <Txt bold size={20}>
        Documents · {data.files.length} submitted
      </Txt>
      {!data.files.length ? (
        <Empty
          title="No documents submitted"
          description="No requirements were uploaded with this application."
          icon="folder"
        />
      ) : (
        data.files.map((file) => (
          <Card key={file.index}>
            <View style={styles.row}>
              <Icon
                name={
                  file.name.toLowerCase().endsWith(".pdf")
                    ? "file-text"
                    : "image"
                }
                size={25}
              />
              <View style={{ flex: 1, minWidth: 180 }}>
                <Txt bold size={14}>
                  {file.name}
                </Txt>
                <Txt muted size={12}>
                  {file.available
                    ? "Submitted requirement"
                    : "File missing or unsupported"}
                </Txt>
              </View>
              <Button
                title="Preview"
                icon="eye"
                variant="secondary"
                compact
                disabled={!file.available}
                onPress={() => setPreview(file)}
              />
              <Button
                title="Download"
                icon="download"
                compact
                loading={busy === file.index}
                disabled={!file.available}
                onPress={() => download(file)}
              />
            </View>
          </Card>
        ))
      )}
      {preview && (
        <Documents
          url={documentUrl(userId, scholarshipId, preview.index)}
          name={preview.name}
          onClose={() => setPreview(null)}
        />
      )}
    </View>
  );
}
export function Inbox({ notify }: ScreenProps) {
  const { data, loading, error, reload } = useResource<{ messages: Message[] }>(
    "messages",
  );
  const [selected, setSelected] = useState<Message | null>(null);
  const [busy, setBusy] = useState(false);
  const remove = async () => {
    if (!selected) return;
    const id = selected.id;
    setSelected(null);
    setBusy(true);
    try {
      await api("delete_message", { id });
      notify("Message deleted.");
      await reload();
    } catch (error) {
      notify((error as Error).message, true);
    } finally {
      setBusy(false);
    }
  };
  return (
    <View style={styles.stack}>
      <PageHeading
        eyebrow="Stay connected"
        title="Messages"
        description="Questions and messages from students and applicants."
      >
        <Button
          title="Refresh"
          icon="refresh-cw"
          variant="secondary"
          compact
          onPress={reload}
        />
      </PageHeading>
      {loading && !data ? (
        <Loader />
      ) : error ? (
        <ResourceError message={error} retry={reload} />
      ) : !data?.messages.length ? (
        <Empty
          title="You’re all caught up"
          description="New messages from students will appear here."
          icon="mail"
        />
      ) : (
        data.messages.map((item) => (
          <Card key={item.id}>
            <View style={styles.row}>
              <View style={{ flex: 1 }}>
                <Txt bold size={18}>
                  {item.name}
                </Txt>
                <Txt muted size={13}>
                  {item.email}
                </Txt>
              </View>
              <Txt muted size={12}>
                {item.created_at}
              </Txt>
            </View>
            <Txt>{item.message}</Txt>
            <View style={styles.row}>
              <Button
                title="Reply by email"
                icon="corner-up-left"
                variant="secondary"
                compact
                onPress={() => {
                  Linking.openURL(`mailto:${item.email}`).catch(() =>
                    notify("An email app could not be opened.", true),
                  );
                }}
              />
              <Button
                title="Delete message"
                icon="trash-2"
                variant="danger"
                compact
                disabled={busy}
                onPress={() => setSelected(item)}
              />
            </View>
          </Card>
        ))
      )}
      {selected && (
        <Confirm
          title="Delete this message?"
          description={`The message from ${selected.name} will be removed.`}
          onConfirm={remove}
          onCancel={() => setSelected(null)}
        />
      )}
    </View>
  );
}
export function Contact({ user, notify }: ScreenProps) {
  const [name, setName] = useState(user.name);
  const [email, setEmail] = useState(user.email);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const { colors } = useTheme();
  const send = async () => {
    setBusy(true);
    setError("");
    try {
      await api("contact", { name, email, message });
      setMessage("");
      notify("Your message has been sent to the scholarship office.");
    } catch (error) {
      setError((error as Error).message);
    } finally {
      setBusy(false);
    }
  };
  return (
    <View style={styles.stack}>
      <PageHeading
        eyebrow="We’re here to help"
        title="Let’s talk."
        description="Reach out to the scholarship office for guidance on your application."
      />
      <View style={styles.grid}>
        <Card style={{ flex: 1, minWidth: 250 }}>
          <Txt size={22} bold>
            Scholarship office
          </Txt>
          {[
            ["mail", office.email],
            ["phone", office.phone],
            ["map-pin", office.address],
          ].map(([icon, text]) => (
            <View
              key={icon}
              style={[
                styles.row,
                {
                  alignItems: "flex-start",
                  flexWrap: "nowrap",
                  marginVertical: 5,
                },
              ]}
            >
              <Icon name={icon as IconName} color={colors.primary} />
              <Txt style={{ flex: 1 }}>{text}</Txt>
            </View>
          ))}
        </Card>
        <Card style={{ flex: 2, minWidth: 280 }}>
          <Txt size={22} bold>
            Send us a message
          </Txt>
          {error && <Notice text={error} error />}
          <Field
            label="Full name"
            value={name}
            onChangeText={setName}
            maxLength={100}
          />
          <Field
            label="Email address"
            value={email}
            onChangeText={setEmail}
            keyboardType="email-address"
            autoCapitalize="none"
          />
          <Field
            label="Your message"
            value={message}
            onChangeText={setMessage}
            multiline
            maxLength={5000}
            placeholder="How can we help you?"
            style={{ minHeight: 160, textAlignVertical: "top" }}
          />
          <Button
            title="Send message"
            icon="send"
            loading={busy}
            onPress={send}
          />
        </Card>
      </View>
    </View>
  );
}
export function FAQ({ navigate }: ScreenProps) {
  const [open, setOpen] = useState<number | null>(0);
  const [search, setSearch] = useState("");
  const { colors } = useTheme();
  return (
    <View style={styles.stack}>
      <PageHeading
        eyebrow="A little clarity"
        title="Frequently asked questions"
        description="Helpful answers for every step of your scholarship journey."
      />
      <Field
        label="Search questions"
        value={search}
        onChangeText={setSearch}
        placeholder="What would you like to know?"
      />
      {faqs
        .map((item, index) => ({ ...item, index }))
        .filter((item) =>
          `${item.question} ${item.answer}`
            .toLowerCase()
            .includes(search.toLowerCase()),
        )
        .map((item) => (
          <Card key={item.index} style={{ padding: 0, overflow: "hidden" }}>
            <Pressable
              accessibilityRole="button"
              accessibilityState={{ expanded: open === item.index }}
              onPress={() => setOpen(open === item.index ? null : item.index)}
              style={{
                flexDirection: "row",
                alignItems: "center",
                padding: 22,
                gap: 16,
              }}
            >
              <Txt bold style={{ flex: 1 }}>
                {item.question}
              </Txt>
              <Icon
                name={open === item.index ? "minus" : "plus"}
                color={colors.primary}
              />
            </Pressable>
            {open === item.index && (
              <Txt muted style={{ paddingHorizontal: 22, paddingBottom: 22 }}>
                {item.answer}
              </Txt>
            )}
          </Card>
        ))}
      <Card>
        <Txt size={20} bold>
          Still have a question?
        </Txt>
        <Txt muted>
          The scholarship office can help with your specific situation.
        </Txt>
        <Button
          title="Contact the office"
          icon="arrow-right"
          onPress={() => navigate({ page: "contact" })}
        />
      </Card>
    </View>
  );
}

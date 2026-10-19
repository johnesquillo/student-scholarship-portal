import React, { useState } from "react";
import {
  ActivityIndicator,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TextInputProps,
  TextProps,
  View,
  ViewStyle,
} from "react-native";
import Feather from "@expo/vector-icons/Feather";
import { useTheme } from "./theme";

export type IconName = React.ComponentProps<typeof Feather>["name"];
export function Icon({
  name,
  size = 19,
  color,
}: {
  name: IconName;
  size?: number;
  color?: string;
}) {
  const { colors } = useTheme();
  return <Feather name={name} size={size} color={color || colors.text} />;
}
export function Txt({
  children,
  size = 15,
  bold = false,
  muted = false,
  style,
  ...props
}: TextProps & { size?: number; bold?: boolean; muted?: boolean }) {
  const { colors } = useTheme();
  return (
    <Text
      {...props}
      style={[
        {
          color: muted ? colors.muted : colors.text,
          fontSize: size,
          lineHeight: size * 1.5,
          fontWeight: bold ? "700" : "400",
        },
        style,
      ]}
    >
      {children}
    </Text>
  );
}
export function Button({
  title,
  onPress,
  icon,
  variant = "primary",
  loading,
  disabled,
  compact,
  style,
}: {
  title: string;
  onPress: () => void;
  icon?: IconName;
  variant?: "primary" | "secondary" | "ghost" | "danger" | "accent";
  loading?: boolean;
  disabled?: boolean;
  compact?: boolean;
  style?: ViewStyle;
}) {
  const { colors } = useTheme();
  const [hovered, setHovered] = useState(false);
  const foreground =
    variant === "accent"
      ? "#183B30"
      : variant === "primary"
        ? colors.onPrimary
        : variant === "danger"
          ? colors.danger
          : colors.text;
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={title}
      accessibilityState={{ disabled: disabled || loading, busy: loading }}
      disabled={disabled || loading}
      onPress={onPress}
      onHoverIn={() => setHovered(true)}
      onHoverOut={() => setHovered(false)}
      style={({ pressed }) => [
        {
          minHeight: compact ? 38 : 48,
          borderRadius: 12,
          paddingHorizontal: compact ? 12 : 18,
          paddingVertical: compact ? 8 : 13,
          flexDirection: "row",
          alignItems: "center",
          justifyContent: "center",
          gap: 9,
          alignSelf: "flex-start",
          backgroundColor:
            variant === "accent"
              ? colors.accent
              : variant === "primary"
                ? colors.primary
                : variant === "danger"
                  ? colors.dangerBg
                  : variant === "secondary"
                    ? colors.soft
                    : "transparent",
          borderWidth: variant === "secondary" ? 1 : 0,
          borderColor: colors.border,
          opacity:
            disabled || loading ? 0.5 : pressed ? 0.72 : hovered ? 0.88 : 1,
          transform: [{ translateY: pressed ? 1 : 0 }],
        },
        style,
      ]}
    >
      {loading ? (
        <ActivityIndicator color={foreground} size="small" />
      ) : icon ? (
        <Icon name={icon} size={compact ? 15 : 17} color={foreground} />
      ) : null}
      <Text
        style={{
          color: foreground,
          fontSize: compact ? 13 : 14,
          fontWeight: "700",
        }}
      >
        {title}
      </Text>
    </Pressable>
  );
}
export function Card({
  children,
  style,
}: React.PropsWithChildren<{ style?: ViewStyle }>) {
  const { colors } = useTheme();
  return (
    <View
      style={[
        {
          backgroundColor: colors.surface,
          borderWidth: 1,
          borderColor: colors.border,
          borderRadius: 20,
          padding: 24,
          gap: 16,
        },
        style,
      ]}
    >
      {children}
    </View>
  );
}
export function Field({
  label,
  error,
  ...props
}: TextInputProps & { label: string; error?: string }) {
  const { colors } = useTheme();
  return (
    <View style={{ gap: 8 }}>
      <Txt size={13} bold>
        {label}
      </Txt>
      <TextInput
        accessibilityLabel={label}
        placeholderTextColor={colors.muted}
        selectionColor={colors.primary}
        {...props}
        style={[
          {
            backgroundColor: colors.input,
            borderColor: error ? colors.danger : colors.border,
            borderWidth: 1,
            borderRadius: 12,
            paddingHorizontal: 14,
            paddingVertical: 13,
            fontSize: 15,
            color: colors.text,
            minHeight: 48,
          },
          props.style,
        ]}
      />
      {error && (
        <Txt size={12} style={{ color: colors.danger }}>
          {error}
        </Txt>
      )}
    </View>
  );
}
export function Notice({
  text,
  error = false,
}: {
  text: string;
  error?: boolean;
}) {
  const { colors } = useTheme();
  return (
    <View
      accessibilityRole="alert"
      style={{
        backgroundColor: error ? colors.dangerBg : colors.successBg,
        padding: 16,
        borderRadius: 12,
        flexDirection: "row",
        gap: 10,
      }}
    >
      <Icon
        name={error ? "alert-circle" : "check-circle"}
        color={error ? colors.danger : colors.primary}
      />
      <Txt size={14} style={{ flex: 1 }}>
        {text}
      </Txt>
    </View>
  );
}
export function Badge({ status }: { status: string }) {
  const { colors } = useTheme();
  const bg =
    status === "Approved"
      ? colors.successBg
      : status === "Rejected"
        ? colors.dangerBg
        : colors.warningBg;
  const fg =
    status === "Approved"
      ? colors.primary
      : status === "Rejected"
        ? colors.danger
        : colors.warning;
  return (
    <View
      style={{
        backgroundColor: bg,
        borderRadius: 20,
        paddingHorizontal: 11,
        paddingVertical: 5,
        alignSelf: "flex-start",
      }}
    >
      <Text style={{ fontSize: 12, fontWeight: "700", color: fg }}>
        {status}
      </Text>
    </View>
  );
}
export function Empty({
  title,
  description,
  icon = "inbox",
  children,
}: React.PropsWithChildren<{
  title: string;
  description: string;
  icon?: IconName;
}>) {
  const { colors } = useTheme();
  return (
    <Card style={{ padding: 40, alignItems: "center" }}>
      <View
        style={{ backgroundColor: colors.soft, padding: 20, borderRadius: 28 }}
      >
        <Icon name={icon} size={30} color={colors.primary} />
      </View>
      <Txt size={20} bold>
        {title}
      </Txt>
      <Txt muted style={{ textAlign: "center", maxWidth: 440 }}>
        {description}
      </Txt>
      {children}
    </Card>
  );
}
export function Loader() {
  const { colors } = useTheme();
  return (
    <View style={{ alignItems: "center", padding: 60, gap: 16 }}>
      <ActivityIndicator size="large" color={colors.primary} />
      <Txt muted>Loading your portal…</Txt>
    </View>
  );
}
export function Select({
  label,
  value,
  options,
  onChange,
}: {
  label: string;
  value: string;
  options: { value: string; label: string }[];
  onChange: (value: string) => void;
}) {
  const { colors } = useTheme();
  const [open, setOpen] = useState(false);
  return (
    <View style={{ gap: 8 }}>
      <Txt size={13} bold>
        {label}
      </Txt>
      <Pressable
        accessibilityRole="button"
        accessibilityLabel={`${label}: ${options.find((o) => o.value === value)?.label || "Choose"}`}
        onPress={() => setOpen(true)}
        style={{
          borderColor: colors.border,
          borderWidth: 1,
          borderRadius: 12,
          padding: 14,
          backgroundColor: colors.input,
          flexDirection: "row",
          alignItems: "center",
          gap: 12,
        }}
      >
        <Txt style={{ flex: 1 }}>
          {options.find((o) => o.value === value)?.label || "Choose an option"}
        </Txt>
        <Icon name="chevron-down" />
      </Pressable>
      <Modal
        visible={open}
        transparent
        animationType="fade"
        onRequestClose={() => setOpen(false)}
      >
        <View style={styles.overlay}>
          <Card style={{ width: "100%", maxWidth: 540, maxHeight: "80%" }}>
            <View style={styles.row}>
              <Txt size={20} bold style={{ flex: 1 }}>
                {label}
              </Txt>
              <Button
                title="Close"
                icon="x"
                compact
                variant="ghost"
                onPress={() => setOpen(false)}
              />
            </View>
            <ScrollView>
              {options.map((option) => (
                <Pressable
                  key={option.value}
                  accessibilityRole="radio"
                  accessibilityState={{ checked: value === option.value }}
                  onPress={() => {
                    onChange(option.value);
                    setOpen(false);
                  }}
                  style={{
                    padding: 16,
                    borderRadius: 10,
                    marginBottom: 4,
                    backgroundColor:
                      option.value === value ? colors.soft : "transparent",
                  }}
                >
                  <Txt bold={option.value === value}>{option.label}</Txt>
                </Pressable>
              ))}
            </ScrollView>
          </Card>
        </View>
      </Modal>
    </View>
  );
}
export function Confirm({
  title,
  description,
  onConfirm,
  onCancel,
}: {
  title: string;
  description: string;
  onConfirm: () => void;
  onCancel: () => void;
}) {
  return (
    <Modal visible transparent animationType="fade" onRequestClose={onCancel}>
      <View style={styles.overlay}>
        <Card style={{ width: "100%", maxWidth: 440 }}>
          <Txt size={22} bold>
            {title}
          </Txt>
          <Txt muted>{description}</Txt>
          <View style={[styles.row, { justifyContent: "flex-end" }]}>
            <Button title="Cancel" variant="secondary" onPress={onCancel} />
            <Button title="Confirm" onPress={onConfirm} />
          </View>
        </Card>
      </View>
    </Modal>
  );
}
export const styles = StyleSheet.create({
  row: {
    flexDirection: "row",
    alignItems: "center",
    flexWrap: "wrap",
    gap: 12,
  },
  stack: { gap: 20 },
  overlay: {
    flex: 1,
    backgroundColor: "#06130CCC",
    alignItems: "center",
    justifyContent: "center",
    padding: 24,
  },
  grid: { flexDirection: "row", flexWrap: "wrap", gap: 20 },
});

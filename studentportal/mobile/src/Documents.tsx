import React, { useState } from "react";
import { Modal, View } from "react-native";
import * as FileSystem from "expo-file-system/legacy";
import * as Sharing from "expo-sharing";
import { documentHeaders } from "./api";
import { Button, Card, Notice, styles, Txt } from "./components";

export async function saveDocument(url: string, filename: string) {
  const path = `${FileSystem.cacheDirectory}portal-${Date.now()}-${filename.replace(/[^a-zA-Z0-9_.-]/g, "_")}`;
  try {
    const result = await FileSystem.downloadAsync(url, path, {
      headers: documentHeaders(),
    });
    if (result.status !== 200)
      throw new Error("This document is missing or unsupported.");
    if (!(await Sharing.isAvailableAsync()))
      throw new Error("Document sharing is unavailable on this device.");
    await Sharing.shareAsync(path, { dialogTitle: filename });
  } finally {
    await FileSystem.deleteAsync(path, { idempotent: true }).catch(() => {});
  }
}
export default function Documents({
  url,
  name,
  onClose,
}: {
  url: string;
  name: string;
  onClose: () => void;
}) {
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  const open = async () => {
    setBusy(true);
    setError("");
    try {
      await saveDocument(url, name);
    } catch (error) {
      setError((error as Error).message);
    } finally {
      setBusy(false);
    }
  };
  return (
    <Modal transparent visible animationType="slide" onRequestClose={onClose}>
      <View style={styles.overlay}>
        <Card style={{ maxWidth: 500, width: "100%" }}>
          <Txt size={20} bold>
            {name}
          </Txt>
          <Txt muted>
            Open or save this requirement with your device’s document viewer.
          </Txt>
          {error && <Notice error text={error} />}
          <Button
            title="Open document"
            icon="external-link"
            loading={busy}
            onPress={open}
          />
          <Button title="Close" variant="secondary" onPress={onClose} />
        </Card>
      </View>
    </Modal>
  );
}

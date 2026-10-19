import React, { useEffect, useState } from "react";
import { Image, Modal, View } from "react-native";
import { documentHeaders } from "./api";
import { Button, Card, Loader, Notice, styles, Txt } from "./components";

export async function saveDocument(url: string, filename: string) {
  const response = await fetch(url, {
    credentials: "include",
    headers: documentHeaders(),
  });
  if (!response.ok)
    throw new Error(
      "This document could not be opened. It may be missing or unsupported.",
    );
  const blob = await response.blob();
  const objectUrl = URL.createObjectURL(blob);
  const anchor = document.createElement("a");
  anchor.href = objectUrl;
  anchor.download = filename;
  document.body.appendChild(anchor);
  anchor.click();
  anchor.remove();
  setTimeout(() => URL.revokeObjectURL(objectUrl), 30000);
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
  const [source, setSource] = useState("");
  const [mime, setMime] = useState("");
  const [error, setError] = useState("");
  useEffect(() => {
    let alive = true;
    let objectUrl = "";
    const controller = new AbortController();
    fetch(url, {
      credentials: "include",
      headers: documentHeaders(),
      signal: controller.signal,
    })
      .then(async (response) => {
        if (!response.ok)
          throw new Error(
            "This document could not be opened. It may be missing or unsupported.",
          );
        const blob = await response.blob();
        if (!alive) return;
        objectUrl = URL.createObjectURL(blob);
        setMime(blob.type);
        setSource(objectUrl);
      })
      .catch((error) => {
        if (alive) setError(error.message);
      });
    return () => {
      alive = false;
      controller.abort();
      if (objectUrl) URL.revokeObjectURL(objectUrl);
    };
  }, [url]);
  return (
    <Modal transparent visible animationType="fade" onRequestClose={onClose}>
      <View style={styles.overlay}>
        <Card style={{ width: "100%", maxWidth: 1100, height: "90%" }}>
          <View style={styles.row}>
            <Txt bold size={17} style={{ flex: 1 }} numberOfLines={2}>
              {name}
            </Txt>
            <Button
              title="Close preview"
              icon="x"
              variant="secondary"
              compact
              onPress={onClose}
            />
          </View>
          {error ? (
            <Notice error text={error} />
          ) : !source ? (
            <Loader />
          ) : mime === "application/pdf" ? (
            React.createElement("iframe", {
              src: source,
              title: name,
              style: {
                width: "100%",
                flex: 1,
                border: 0,
                borderRadius: 12,
                backgroundColor: "#fff",
              },
            })
          ) : (
            <Image
              accessibilityLabel={name}
              source={{ uri: source }}
              resizeMode="contain"
              style={{ flex: 1, width: "100%" }}
            />
          )}
        </Card>
      </View>
    </Modal>
  );
}

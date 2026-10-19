import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useState,
} from "react";
import { onSessionExpired, restoreSession } from "./api";
import { User } from "./types";

type Message = { text: string; error: boolean } | null;
type Session = {
  user: User | null;
  setUser: React.Dispatch<React.SetStateAction<User | null>>;
  ready: boolean;
  failure: string;
  boot: () => Promise<void>;
  notice: Message;
  setNotice: React.Dispatch<React.SetStateAction<Message>>;
  authNotice: string;
  setAuthNotice: React.Dispatch<React.SetStateAction<string>>;
};
const Context = createContext<Session | null>(null);
export function SessionProvider({ children }: React.PropsWithChildren) {
  const [user, setUser] = useState<User | null>(null);
  const [ready, setReady] = useState(false);
  const [failure, setFailure] = useState("");
  const [notice, setNotice] = useState<Message>(null);
  const [authNotice, setAuthNotice] = useState("");
  const boot = useCallback(async () => {
    setFailure("");
    try {
      const result = await restoreSession();
      setUser(result.user);
    } catch (error) {
      setFailure((error as Error).message);
    } finally {
      setReady(true);
    }
  }, []);
  useEffect(() => {
    onSessionExpired(() => {
      setUser(null);
      setNotice(null);
    });
    let active = true;
    restoreSession()
      .then((result) => {
        if (active) setUser(result.user);
      })
      .catch((error) => {
        if (active) setFailure((error as Error).message);
      })
      .finally(() => {
        if (active) setReady(true);
      });
    return () => {
      active = false;
    };
  }, []);
  return (
    <Context.Provider
      value={{
        user,
        setUser,
        ready,
        failure,
        boot,
        notice,
        setNotice,
        authNotice,
        setAuthNotice,
      }}
    >
      {children}
    </Context.Provider>
  );
}
export function useSession() {
  const session = useContext(Context);
  if (!session) throw new Error("Missing session provider");
  return session;
}

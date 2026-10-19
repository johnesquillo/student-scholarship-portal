import React, { createContext, useContext, useState } from "react";

const SidebarContext = createContext<{
  visible: boolean;
  setVisible: React.Dispatch<React.SetStateAction<boolean>>;
} | null>(null);

export function SidebarProvider({ children }: React.PropsWithChildren) {
  const [visible, setVisible] = useState(true);
  return (
    <SidebarContext.Provider value={{ visible, setVisible }}>
      {children}
    </SidebarContext.Provider>
  );
}

export function useSidebar() {
  const sidebar = useContext(SidebarContext);
  if (!sidebar) throw new Error("Missing sidebar provider");
  return sidebar;
}

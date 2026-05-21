import * as React from "react";
import { cn } from "@/lib/utils";

interface TabsContextValue {
    value: string;
    onValueChange: (value: string) => void;
}

const TabsContext = React.createContext<TabsContextValue | null>(null);

function useTabs() {
    const ctx = React.useContext(TabsContext);
    if (!ctx) throw new Error("Tabs components must be used within <Tabs>");
    return ctx;
}

function Tabs({ value, onValueChange, defaultValue, className, children, ...props }: {
    value?: string; onValueChange?: (value: string) => void; defaultValue?: string;
} & React.HTMLAttributes<HTMLDivElement>) {
    const [internalValue, setInternalValue] = React.useState(defaultValue ?? value ?? "");
    const isControlled = value !== undefined && onValueChange !== undefined;
    const activeValue = isControlled ? value : internalValue;
    const handleChange = React.useCallback((v: string) => {
        if (isControlled) { onValueChange(v); } else { setInternalValue(v); }
    }, [isControlled, onValueChange]);
    return (
        <TabsContext.Provider value={{ value: activeValue, onValueChange: handleChange }}>
            <div data-slot="tabs" className={cn("w-full", className)} {...props}>
                {children}
            </div>
        </TabsContext.Provider>
    );
}

function TabsList({ className, children, ...props }: React.HTMLAttributes<HTMLDivElement>) {
    return (
        <div data-slot="tabs-list" role="tablist" className={cn(
            "inline-flex h-9 w-full items-center justify-start gap-1 rounded-lg bg-muted p-1 text-muted-foreground",
            className
        )} {...props}>
            {children}
        </div>
    );
}

function TabsTrigger({ value, className, children, ...props }: {
    value: string;
} & React.ButtonHTMLAttributes<HTMLButtonElement>) {
    const { value: selected, onValueChange } = useTabs();
    const isActive = selected === value;
    return (
        <button
            data-slot="tabs-trigger"
            role="tab"
            type="button"
            aria-selected={isActive}
            onClick={() => onValueChange(value)}
            className={cn(
                "inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50",
                isActive ? "bg-background text-foreground shadow-xs" : "hover:bg-background/50 hover:text-foreground",
                className
            )}
            {...props}
        >
            {children}
        </button>
    );
}

function TabsContent({ value, className, children, ...props }: {
    value: string;
} & React.HTMLAttributes<HTMLDivElement>) {
    const { value: selected } = useTabs();
    if (selected !== value) return null;
    return (
        <div data-slot="tabs-content" role="tabpanel" className={cn("mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2", className)} {...props}>
            {children}
        </div>
    );
}

export { Tabs, TabsList, TabsTrigger, TabsContent };

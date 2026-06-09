import { cn } from "@/lib/utils"

function Progress({ className, value, ...props }: React.ComponentProps<"div"> & { value?: number }) {
    return (
        <div
            data-slot="progress"
            className={cn("bg-primary/20 h-2 w-full overflow-hidden rounded-full", className)}
            {...props}
        >
            <div
                className="bg-primary h-full transition-all"
                style={{ width: `${Math.min(100, Math.max(0, value ?? 0))}%` }}
            />
        </div>
    )
}

export { Progress }

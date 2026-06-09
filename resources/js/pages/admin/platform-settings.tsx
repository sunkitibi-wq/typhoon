import { Head, useForm } from '@inertiajs/react';
import { Settings, Save } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';

interface Setting {
    id: number; key: string; value: string | null; group: string;
    type: string; description: string | null;
}

export default function PlatformSettings({ settings }: { settings: Record<string, Setting[]> }) {
    const { data, setData, post, processing } = useForm<Record<string, string>>({});

    const groups = Object.entries(settings);

    const initData = () => {
        const initial: Record<string, string> = {};
        groups.forEach(([, items]) => items.forEach(s => { initial[s.key] = s.value ?? ''; }));
        return initial;
    };

    if (Object.keys(data).length === 0 && groups.length > 0) {
        const initial = initData();
        Object.assign(data, initial);
    }

    const submit = (e: React.FormEvent) => { e.preventDefault(); post(route('admin.platform-settings.update')); };

    return (
        <>
            <Head title="Platform Settings" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">Platform Settings</h1>

                <form onSubmit={submit} className="space-y-6">
                    {groups.map(([group, items]) => (
                        <Card key={group}>
                            <CardHeader>
                                <CardTitle className="capitalize">{group.replace(/_/g, ' ')}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {items.map(setting => (
                                    <div key={setting.id} className="space-y-1">
                                        <div className="flex items-center gap-2">
                                            <Label className="font-mono text-xs">{setting.key}</Label>
                                            <Badge variant="outline" className="text-[10px]">{setting.type}</Badge>
                                        </div>
                                        {setting.type === 'boolean' ? (
                                            <select
                                                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                                                value={data[setting.key] ?? 'false'}
                                                onChange={e => setData(setting.key, e.target.value)}
                                            >
                                                <option value="true">Enabled</option>
                                                <option value="false">Disabled</option>
                                            </select>
                                        ) : setting.type === 'textarea' ? (
                                            <textarea
                                                className="flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                                                value={data[setting.key] ?? ''}
                                                onChange={e => setData(setting.key, e.target.value)}
                                            />
                                        ) : (
                                            <Input
                                                type={setting.type === 'number' ? 'number' : setting.type === 'password' ? 'password' : 'text'}
                                                value={data[setting.key] ?? ''}
                                                onChange={e => setData(setting.key, e.target.value)}
                                                step={setting.type === 'number' ? 'any' : undefined}
                                            />
                                        )}
                                        {setting.description && <p className="text-xs text-muted-foreground">{setting.description}</p>}
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    ))}

                    <Button type="submit" disabled={processing} className="w-full md:w-auto">
                        <Save className="mr-1 h-4 w-4" />Save All Settings
                    </Button>
                </form>
            </div>
        </>
    );
}

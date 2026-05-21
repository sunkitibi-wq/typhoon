import { Head, useForm } from '@inertiajs/react';
import { UserPlus, Save, ArrowLeft } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Link } from '@inertiajs/react';

interface Role { id: number; name: string; }
interface UserData { id: number; name: string; email: string; phone: string | null; status: string; role_id: number; }

export default function UserForm({ roles, user }: { roles: Role[]; user: UserData | null }) {
    const isEdit = !!user;
    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
        role_id: user?.role_id ? String(user.role_id) : '',
        phone: user?.phone ?? '',
        status: user?.status ?? 'active',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            post(route('admin.users.edit', user!.id));
        } else {
            post(route('admin.users.create'));
        }
    };

    return (
        <>
            <Head title={isEdit ? 'Edit User' : 'Create User'} />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center gap-4">
                    <Link href={route('admin.users')} className="text-sm text-muted-foreground hover:text-foreground flex items-center gap-1">
                        <ArrowLeft className="h-4 w-4" />Back to Users
                    </Link>
                </div>
                <h1 className="text-2xl font-bold">{isEdit ? 'Edit User' : 'Create User'}</h1>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>{isEdit ? <Save className="mr-2 inline h-4 w-4" /> : <UserPlus className="mr-2 inline h-4 w-4" />}
                            {isEdit ? 'Update User' : 'New User'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Name *</Label>
                                    <Input value={data.name} onChange={e => setData('name', e.target.value)} required />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Email *</Label>
                                    <Input type="email" value={data.email} onChange={e => setData('email', e.target.value)} required />
                                    <InputError message={errors.email} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Password {isEdit ? '(leave empty to keep)' : '*'}</Label>
                                    <Input type="password" value={data.password} onChange={e => setData('password', e.target.value)} required={!isEdit} />
                                    <InputError message={errors.password} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Confirm Password</Label>
                                    <Input type="password" value={data.password_confirmation} onChange={e => setData('password_confirmation', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Role *</Label>
                                    <Select value={data.role_id} onValueChange={v => setData('role_id', v)}>
                                        <SelectTrigger><SelectValue placeholder="Select role" /></SelectTrigger>
                                        <SelectContent>{roles.map(r => (<SelectItem key={r.id} value={String(r.id)}>{r.name}</SelectItem>))}</SelectContent>
                                    </Select>
                                    <InputError message={errors.role_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Phone</Label>
                                    <Input value={data.phone} onChange={e => setData('phone', e.target.value)} />
                                </div>
                                {isEdit && (
                                    <div className="space-y-2">
                                        <Label>Status</Label>
                                        <Select value={data.status} onValueChange={v => setData('status', v)}>
                                            <SelectTrigger><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="active">Active</SelectItem>
                                                <SelectItem value="suspended">Suspended</SelectItem>
                                                <SelectItem value="banned">Banned</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                )}
                            </div>
                            <Button type="submit" disabled={processing}>
                                {isEdit ? <Save className="mr-1 h-4 w-4" /> : <UserPlus className="mr-1 h-4 w-4" />}
                                {isEdit ? 'Update User' : 'Create User'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
